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

namespace App\Listener;

use App\Log\Channels;
use App\Log\EventDuration;
use App\Log\RedactLogProcessor;
use App\Trace\CodePoint;
use App\Trace\Otel\OtelBootstrap;
use App\Trace\Otel\TraceScope;
use Hyperf\Command\Event\AfterExecute;
use Hyperf\Command\Event\BeforeHandle;
use Hyperf\Context\Context;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Event\Annotation\Listener;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Logger\LoggerFactory;
use OpenTelemetry\API\Globals;
use OpenTelemetry\API\Trace\SpanInterface;
use OpenTelemetry\API\Trace\SpanKind;
use OpenTelemetry\API\Trace\StatusCode;
use OpenTelemetry\Context\ScopeInterface;
use OpenTelemetry\SemConv\Attributes\CodeAttributes;
use OpenTelemetry\SemConv\Attributes\ErrorAttributes;
use OpenTelemetry\SemConv\Incubating\Attributes\ProcessIncubatingAttributes;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * CLI 命令与定时任务的追踪边界（spec §5.4）。
 *
 * - BeforeHandle / crontab BeforeExecute：起 root INTERNAL Span（名字 = 命令名 / 任务名），
 *   记 process.* 属性与（开关开启时）命令类定义处的代码位置；
 * - AfterExecute / crontab AfterExecute / FailToExecute：补 process.exit.code（有异常 1，否则 0；
 *   非 0 → ERROR + recordException），在 Span 仍激活时写完成日志 cli.command，
 *   随后 detach → end → flush。
 *
 * 事件间状态放协程本地 Context（监听器是单例，禁放属性——稳定性 spec §3.1）。
 * 命令的 Before/After 在同一协程（Command::execute() 的 try/finally，已核实 vendor 源码）；
 * crontab 三事件在同一 Timer 回调协程（已核实 hyperf/crontab 3.2 源码）。
 * 用栈存多份：crontab 里再跑命令（命令型定时任务）时两层 Span 各自收尾、互不覆盖。
 *
 * 正常退出才保证上报：kill -9 / OOM / 未捕获致命错误时 AfterExecute 不执行，
 * 批次内 Span 随进程丢失——BatchSpanProcessor 固有行为（spec §5.4），非缺陷。
 */
#[Listener]
class TraceBoundaryListener implements ListenerInterface
{
    private const TRACER_NAME = 'app';

    /** 协程本地栈，元素 [SpanInterface, ScopeInterface, float $startedAt]。 */
    private const SCOPES = 'trace.cli.scopes';

    private const CRON_BEFORE = 'Hyperf\Crontab\Event\BeforeExecute';

    private const CRON_AFTER = 'Hyperf\Crontab\Event\AfterExecute';

    private const CRON_FAIL = 'Hyperf\Crontab\Event\FailToExecute';

    private LoggerInterface $logger;

    public function __construct(private ConfigInterface $config, LoggerFactory $loggerFactory)
    {
        $this->logger = $loggerFactory->get(Channels::CLI);
    }

    public function listen(): array
    {
        // 只接命令的 BeforeHandle + AfterExecute：AfterExecute 在 finally 里恒派发一次且带 getThrowable()，
        // 异常路径由它覆盖，不接 FailToHandle（避免双重收尾）。
        $events = [
            BeforeHandle::class,
            AfterExecute::class,
        ];
        foreach ([self::CRON_BEFORE, self::CRON_AFTER, self::CRON_FAIL] as $class) {
            if (class_exists($class)) {
                $events[] = $class;
            }
        }

        return $events;
    }

    public function process(object $event): void
    {
        if ($event instanceof BeforeHandle || $this->is($event, self::CRON_BEFORE)) {
            $this->begin($event);

            return;
        }

        if ($event instanceof AfterExecute || $this->is($event, self::CRON_AFTER) || $this->is($event, self::CRON_FAIL)) {
            $this->finish($event);
        }
    }

    private function begin(object $event): void
    {
        $name = $this->eventName($event);
        $span = Globals::tracerProvider()
            ->getTracer(self::TRACER_NAME)
            ->spanBuilder($name)
            ->setSpanKind(SpanKind::KIND_INTERNAL)
            // 不设 parent：无上游的进程内根 Span。嵌套（crontab 里跑命令）由 OTel 当前上下文自动挂父。
            ->setAttributes($this->startAttributes($event, $name))
            ->startSpan();

        $scope = $span->activate();
        $scopes = Context::get(self::SCOPES, []);
        $scopes[] = [$span, $scope, microtime(true)];
        Context::set(self::SCOPES, $scopes);
    }

    private function finish(object $event): void
    {
        $scopes = Context::get(self::SCOPES, []);
        $record = array_pop($scopes);
        if (! is_array($record)) {
            return; // 没有配对的 begin（别处手工派发的 AfterExecute）：忽略。
        }
        if ($scopes === []) {
            Context::destroy(self::SCOPES);
        } else {
            Context::set(self::SCOPES, $scopes);
        }

        /* @var array{0: SpanInterface, 1: ScopeInterface, 2: float} $record */
        [$span, $scope, $startedAt] = $record;
        $throwable = $this->throwable($event);
        $exitCode = $throwable === null ? 0 : 1;

        try {
            $span->setAttribute(ProcessIncubatingAttributes::PROCESS_EXIT_CODE, $exitCode);
            if ($throwable === null) {
                $span->setStatus(StatusCode::STATUS_OK);
            } else {
                $span->setAttribute(ErrorAttributes::ERROR_TYPE, $throwable::class);
                $span->recordException($throwable);
                $span->setStatus(StatusCode::STATUS_ERROR, $throwable->getMessage());
            }

            // 完成日志必须在 Span 仍激活时写：Task 10 的 TraceContextProcessor 从当前 Span 取 trace.id。
            $context = EventDuration::field($startedAt) + [
                'event.outcome' => $throwable === null ? 'success' : 'failure',
                'process.name' => $this->eventName($event),
                'process.executable' => PHP_BINARY,
                'process.command_line' => implode(' ', $this->argv()),
                'process.exit_code' => $exitCode,
            ];
            if ($throwable === null) {
                $this->logger->info('cli.command', $context);
            } else {
                // 传 Throwable 对象即可：ErrorNormalizer 统一展开为 error 对象（与其它通道同口径）。
                $this->logger->error('cli.command', $context + ['exception' => $throwable]);
            }
        } finally {
            // Span 结束之后本协程的日志（事后 QueryExecuted、退出前补记）拿不到活动 Span：
            // 收尾前把字段快照进协程，TraceContextProcessor 经 remembered() 兜底（与 TraceMiddleware 同口径）。
            TraceScope::remember(TraceScope::currentTraceFields());
            $scope->detach();
            $span->end();
            OtelBootstrap::flush();
        }
    }

    /** @return array<string, mixed> */
    private function startAttributes(object $event, string $name): array
    {
        $attributes = [
            ProcessIncubatingAttributes::PROCESS_COMMAND => $name,
            ProcessIncubatingAttributes::PROCESS_COMMAND_ARGS => $this->argv(),
            ProcessIncubatingAttributes::PROCESS_PID => getmypid(),
            ProcessIncubatingAttributes::PROCESS_EXECUTABLE_NAME => basename(PHP_BINARY),
            ProcessIncubatingAttributes::PROCESS_EXECUTABLE_PATH => PHP_BINARY,
        ];

        // 代码位置（spec §5.1：仅 HTTP 入口 / CLI 根 Span）：命令记类定义处；crontab 没有命令类，跳过。
        if ($this->config->get('trace.otel.code_point_server', true) && method_exists($event, 'getCommand')) {
            $command = $event->getCommand();
            if (is_object($command)) {
                [$file, $line] = CodePoint::command($command::class);
                $attributes[CodeAttributes::CODE_FILE_PATH] = $file;
                $attributes[CodeAttributes::CODE_LINE_NUMBER] = $line;
            }
        }

        return $attributes;
    }

    /**
     * 脱敏后的 argv（spec §6.4：命令行"经脱敏"）。RedactLogProcessor 只按键名脱敏结构化字段，
     * 对字符串值无效，这里对 `--key=value` 形式做值级兜底：key 命中 trace.redact_keys
     * （默认 RedactLogProcessor::DEFAULT_KEYS，忽略大小写与 _/-）。空格分隔的 `--key value`
     * 无法识别（不知道哪些参数带值）——约定密钥一律用 `--key=value` 传（写入 Task 13 运维文档）。
     *
     * @return list<string>
     */
    private function argv(): array
    {
        $keys = $this->redactKeys();
        $parts = [];
        foreach ($_SERVER['argv'] ?? [] as $arg) {
            $arg = (string) $arg;
            if (preg_match('/\A(--?[^=]+)=(.*)\z/s', $arg, $matches) === 1 && isset($keys[RedactLogProcessor::normalize($matches[1])])) {
                $parts[] = $matches[1] . '=***';
                continue;
            }
            $parts[] = $arg;
        }

        return $parts;
    }

    /** @return array<string, true> */
    private function redactKeys(): array
    {
        // 与 RedactLogProcessor::resolveKeys() 同算法但故意不合并：这里读的是注入的 ConfigInterface
        // （用例可传局部配置，独立于全局 config()），RedactLogProcessor 走全局 config() 且带 $keys 覆写语义。
        $configured = $this->config->get('trace.redact_keys');
        $keys = is_array($configured) && $configured !== [] ? $configured : RedactLogProcessor::DEFAULT_KEYS;

        $normalized = [];
        foreach ($keys as $key) {
            $normalized[RedactLogProcessor::normalize((string) $key)] = true;
        }

        return $normalized;
    }

    /**
     * 命令名（fallback 类名）/ 定时任务名（Crontab::getName()，fallback 事件类名）。
     */
    private function eventName(object $event): string
    {
        if (method_exists($event, 'getCommand')) {
            $command = $event->getCommand();
            if (is_object($command)) {
                $name = method_exists($command, 'getName') ? $command->getName() : null;

                return is_string($name) && $name !== '' ? $name : $command::class;
            }
        }

        // crontab 事件（未安装 hyperf/crontab 时到不了）：public 属性 $crontab（已核实 3.2 源码）。
        $crontab = $event->crontab ?? null;
        if (is_object($crontab) && method_exists($crontab, 'getName')) {
            $name = $crontab->getName();
            if (is_string($name) && $name !== '') {
                return $name;
            }
        }

        return $event::class;
    }

    /**
     * 命令 AfterExecute::getThrowable() 与 crontab FailToExecute::getThrowable() 都有（已核实源码）；
     * crontab AfterExecute 没有该方法 → null（成功）。
     */
    private function throwable(object $event): ?Throwable
    {
        $throwable = method_exists($event, 'getThrowable') ? $event->getThrowable() : null;

        return $throwable instanceof Throwable ? $throwable : null;
    }

    private function is(object $event, string $class): bool
    {
        return class_exists($class) && $event instanceof $class;
    }
}
