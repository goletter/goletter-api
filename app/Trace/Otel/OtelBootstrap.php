<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace App\Trace\Otel;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Context\ApplicationContext;
use Hyperf\Coroutine\Coroutine;
use Hyperf\Coroutine\Locker;
use InvalidArgumentException;
use OpenTelemetry\API\Globals;
use OpenTelemetry\API\Instrumentation\Configurator;
use OpenTelemetry\API\Trace\Propagation\TraceContextPropagator;
use OpenTelemetry\Context\Context;
use OpenTelemetry\Context\ContextStorage;
use OpenTelemetry\Contrib\Context\Swoole\SwooleContextStorage;
use OpenTelemetry\Contrib\Otlp\ContentTypes;
use OpenTelemetry\SDK\Common\Attribute\Attributes;
use OpenTelemetry\SDK\Common\Export\Http\PsrTransportFactory;
use OpenTelemetry\SDK\Common\Time\ClockFactory;
use OpenTelemetry\SDK\Resource\ResourceInfo;
use OpenTelemetry\SDK\Trace\Sampler\AlwaysOffSampler;
use OpenTelemetry\SDK\Trace\Sampler\AlwaysOnSampler;
use OpenTelemetry\SDK\Trace\Sampler\ParentBased;
use OpenTelemetry\SDK\Trace\Sampler\TraceIdRatioBasedSampler;
use OpenTelemetry\SDK\Trace\SamplerInterface;
use OpenTelemetry\SDK\Trace\SpanExporterInterface;
use OpenTelemetry\SDK\Trace\SpanProcessor\BatchSpanProcessor;
use OpenTelemetry\SDK\Trace\SpanProcessorInterface;
use OpenTelemetry\SDK\Trace\TracerProvider;
use OpenTelemetry\SemConv\ResourceAttributes;

use function Hyperf\Support\env;

/**
 * OTel 启动装配。bin/hyperf.php 在容器就绪后、server 启动前调用一次；
 * 测试引导（HyperfTest\Support\Tracing）传 InMemoryExporter 调用。
 *
 * 幂等：Globals 首次取用即缓存 Configurator，重复注册无效，这里用 $booted 挡住二次调用。
 * endpoint 为空且未注入 exporter → 不建 processor：Span 照常创建与记录，只是被 Noop 处理器丢弃，
 * 不导出、无网络开销（本地开销极小但非零）。
 */
final class OtelBootstrap
{
    private const FLUSH_LOCK = 'trace:flush';

    private static bool $booted = false;

    private static bool $enabled = false;

    private static ?SpanProcessorInterface $processor = null;

    public static function boot(?SpanExporterInterface $exporter = null): void
    {
        if (self::$booted) {
            return;
        }

        // Swoole 协程本地上下文（协程外 cid=-1 时自动退化为进程级存储，无需分支）。
        if (extension_loaded('swoole')) {
            Context::setStorage(new SwooleContextStorage(new ContextStorage()));
        }

        $config = ApplicationContext::getContainer()->get(ConfigInterface::class);
        $endpoint = (string) $config->get('trace.otel.endpoint', '');

        if ($exporter === null && trim($endpoint) !== '') {
            $exporter = self::buildExporter($config);
        }
        self::$enabled = $exporter !== null;

        self::$processor = $exporter === null ? null : new BatchSpanProcessor(
            $exporter,
            ClockFactory::getDefault(),
            maxQueueSize: (int) $config->get('trace.otel.max_queue_size', 2048),
            scheduledDelayMillis: (int) $config->get('trace.otel.flush_interval_ms', 2000),
            maxExportBatchSize: (int) $config->get('trace.otel.max_export_batch', 512),
            // 禁止队列满时同步导出（会在业务协程里发 HTTP，阻塞请求）；溢出由 Span 上限兜底。
            autoFlush: false,
        );

        $provider = new TracerProvider(
            self::$processor === null ? [] : [self::$processor],
            // Span 上限采样器包在 ParentBased 外层：先按父/比例决策，再限单 Trace 总量。
            new TraceSpanLimitSampler(
                self::buildSampler($config),
                (int) $config->get('trace.otel.max_spans_per_trace', 500),
            ),
            self::buildResource($config),
        );

        Globals::registerInitializer(static fn (Configurator $configurator): Configurator => $configurator
            ->withTracerProvider($provider)
            ->withPropagator(TraceContextPropagator::getInstance()));

        // 成功路径末尾才置位：中途抛异常（配置非法等）不留下"半初始化但 booted"的静默 no-op 状态，
        // 修正配置后再次 boot() 仍会真实重试。
        self::$booted = true;
    }

    public static function enabled(): bool
    {
        return self::$enabled;
    }

    /**
     * 导出队列里已结束的 Span。协程内用 Locker 防并发（定时器与请求收尾可能撞车），
     * 拿不到锁的调用会等锁释放后直接跳过——数据已被持锁者导出，留在队列里的等下一轮。
     * 协程外（CLI 收尾 / PHPUnit）直接导出。
     */
    public static function flush(): void
    {
        if (self::$processor === null) {
            return;
        }

        if (Coroutine::id() > 0) {
            if (! Locker::lock(self::FLUSH_LOCK)) {
                return;
            }
            try {
                self::$processor->forceFlush();
            } finally {
                Locker::unlock(self::FLUSH_LOCK);
            }

            return;
        }

        self::$processor->forceFlush();
    }

    /** 进程退出前收尾：排空队列 + 导出 + 关闭 exporter。不抢锁（进程即将消失，必须走完）。 */
    public static function shutdown(): void
    {
        $processor = self::$processor;
        if ($processor === null) {
            return;
        }
        self::$processor = null;
        $processor->shutdown();
    }

    /**
     * 只写 host[:port] 时补默认路径 /v1/traces；空串原样返回（= 不启用）。
     * 非法（缺 scheme / 非 http(s) / 带 query 或 fragment）直接抛异常：宁可起不来，也不要带病运行。
     */
    public static function normalizeEndpoint(string $endpoint): string
    {
        $endpoint = trim($endpoint);
        if ($endpoint === '') {
            return '';
        }

        $parts = parse_url($endpoint);
        $scheme = $parts['scheme'] ?? null;
        if ($parts === false || $scheme === null || ! isset($parts['host'])) {
            throw new InvalidArgumentException(sprintf('OTEL_EXPORTER_OTLP_ENDPOINT 非法（缺少 scheme 或 host）: %s', $endpoint));
        }
        if (! in_array($scheme, ['http', 'https'], true)) {
            throw new InvalidArgumentException(sprintf('OTEL_EXPORTER_OTLP_ENDPOINT 只支持 http(s)（无 ext-grpc）: %s', $endpoint));
        }
        // query/fragment 拼进 /v1/traces 会得到语义不明的 URL（多为把 token 误写进端点），直接拒绝。
        if (! empty($parts['query']) || ! empty($parts['fragment'])) {
            throw new InvalidArgumentException(sprintf('OTEL_EXPORTER_OTLP_ENDPOINT 不允许带 query/fragment: %s', $endpoint));
        }
        if (! isset($parts['path']) || $parts['path'] === '' || $parts['path'] === '/') {
            $endpoint = rtrim($endpoint, '/') . '/v1/traces';
        }

        return $endpoint;
    }

    /** @return array<string, string> k=v,k2=v2 格式；缺 = 或键为空直接抛异常（启动期暴露配置错误）。 */
    public static function parseHeaders(string $raw): array
    {
        $headers = [];
        foreach (explode(',', $raw) as $pair) {
            $pair = trim($pair);
            if ($pair === '') {
                continue;
            }
            $pos = strpos($pair, '=');
            if ($pos === false) {
                throw new InvalidArgumentException(sprintf('OTEL_EXPORTER_OTLP_HEADERS 非法（需要 k=v 对）: %s', $pair));
            }
            $key = trim(substr($pair, 0, $pos));
            if ($key === '') {
                throw new InvalidArgumentException(sprintf('OTEL_EXPORTER_OTLP_HEADERS 非法（键不能为空）: %s', $pair));
            }
            $headers[$key] = trim(substr($pair, $pos + 1));
        }

        return $headers;
    }

    private static function buildExporter(ConfigInterface $config): SpanExporterInterface
    {
        $protocol = (string) $config->get('trace.otel.protocol', 'http/protobuf');
        if ($protocol !== 'http/protobuf') {
            throw new InvalidArgumentException(sprintf('只支持 OTLP http/protobuf（无 ext-grpc）: %s', $protocol));
        }

        $compression = (string) $config->get('trace.otel.compression', 'none');
        $timeout = (float) $config->get('trace.otel.timeout_ms', 10000) / 1000;

        // 用原生 Guzzle（curl handler）而不是 hyperf/guzzle 的 CoroutineHandler：
        // worker 退出钩子（OnWorkerExit）不在协程里，CoroutineHandler 在 cid=-1 会炸；
        // curl 走 Swoole hook：协程内自动调度，协程外退化为阻塞调用（退出时正是想要的）。
        $factory = new HttpFactory();
        $transport = (new PsrTransportFactory(new Client(['timeout' => $timeout]), $factory, $factory))->create(
            self::normalizeEndpoint((string) $config->get('trace.otel.endpoint', '')),
            ContentTypes::PROTOBUF,
            self::parseHeaders((string) $config->get('trace.otel.headers', '')),
            in_array($compression, ['', 'none'], true) ? null : $compression,
            $timeout,
        );

        return new \OpenTelemetry\Contrib\Otlp\SpanExporter($transport);
    }

    private static function buildSampler(ConfigInterface $config): SamplerInterface
    {
        $arg = (float) $config->get('trace.otel.sampler_arg', 1.0);

        return match ((string) $config->get('trace.otel.sampler', 'parentbased_traceidratio')) {
            'always_on' => new AlwaysOnSampler(),
            'always_off' => new AlwaysOffSampler(),
            'traceidratio' => new TraceIdRatioBasedSampler($arg),
            'parentbased_always_on' => new ParentBased(new AlwaysOnSampler()),
            'parentbased_always_off' => new ParentBased(new AlwaysOffSampler()),
            'parentbased_traceidratio' => new ParentBased(new TraceIdRatioBasedSampler($arg)),
            default => throw new InvalidArgumentException(
                sprintf('未知采样器 trace.otel.sampler=%s', (string) $config->get('trace.otel.sampler'))
            ),
        };
    }

    private static function buildResource(ConfigInterface $config): ResourceInfo
    {
        $serviceName = (string) $config->get('trace.otel.service_name', 'hyperf-base-service');
        $module = (string) $config->get('trace.otel.module', '');

        return ResourceInfo::create(Attributes::create([
            ResourceAttributes::SERVICE_NAME => $serviceName,
            ResourceAttributes::SERVICE_VERSION => (string) $config->get('trace.otel.service_version', 'dev'),
            ResourceAttributes::HOST_NAME => (string) $config->get('trace.otel.host_name', 'unknown'),
            ResourceAttributes::HOST_IP => (string) $config->get('trace.otel.host_ip', '127.0.0.1'),
            ResourceAttributes::DEPLOYMENT_ENVIRONMENT_NAME => (string) env('APP_ENV', 'dev'),
            'service.module' => $module === '' ? $serviceName : $module,
        ]));
    }
}
