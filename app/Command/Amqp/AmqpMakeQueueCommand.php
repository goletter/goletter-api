<?php

declare(strict_types=1);

namespace App\Command\Amqp;

use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

/**
 * 一键生成分片队列三件套，并写入 AccountQueueRegistry。
 *
 *   php bin/hyperf.php amqp:make-queue accountRefund
 *   php bin/hyperf.php amqp:make-queue accountRefund --label=账户退款
 *   php bin/hyperf.php amqp:make-queue accountRefund --serial=tenant_id,account_id
 *   php bin/hyperf.php amqp:make-queue accountRefund --force
 */
#[Command]
class AmqpMakeQueueCommand extends HyperfCommand
{
    public function __construct()
    {
        parent::__construct('amqp:make-queue');
    }

    public function configure(): void
    {
        parent::configure();
        $this->setDescription('生成分片 Queue/Producer/Consumer，并注册到 AccountQueueRegistry');
        $this->addArgument('name', InputArgument::REQUIRED, '业务名，如 accountRefund / AccountRefund');
        $this->addOption('label', null, InputOption::VALUE_REQUIRED, '中文说明（注释与日志用）');
        $this->addOption('serial', null, InputOption::VALUE_REQUIRED, '串行字段，逗号分隔，默认 tenant_id,busine_id');
        $this->addOption('force', 'f', InputOption::VALUE_NONE, '已存在时覆盖');
        $this->addOption('no-register', null, InputOption::VALUE_NONE, '不写入 AccountQueueRegistry');
    }

    public function handle()
    {
        $raw = (string) $this->input->getArgument('name');
        [$studly, $exchange] = $this->normalizeName($raw);
        if ($studly === '' || $exchange === '') {
            $this->error('无效名称：' . $raw);
            return;
        }

        $label = (string) ($this->input->getOption('label') ?: $exchange);
        $force = (bool) $this->input->getOption('force');
        $serialFields = $this->parseSerialFields(
            (string) ($this->input->getOption('serial') ?: 'tenant_id,busine_id')
        );
        if ($serialFields === []) {
            $this->error('--serial 不能为空，例如 tenant_id,busine_id');
            return;
        }

        $queueClass = $studly . 'Queue';
        $producerClass = $studly . 'Producer';
        $consumerClass = $studly . 'Consumer';

        $files = [
            BASE_PATH . '/app/Amqp/Queue/' . $queueClass . '.php' => $this->queueStub($queueClass, $producerClass, $consumerClass, $exchange, $label, $serialFields),
            BASE_PATH . '/app/Amqp/Producer/' . $producerClass . '.php' => $this->producerStub($queueClass, $producerClass),
            BASE_PATH . '/app/Amqp/Consumer/' . $consumerClass . '.php' => $this->consumerStub($queueClass, $consumerClass, $label),
        ];

        foreach ($files as $path => $contents) {
            if (is_file($path) && ! $force) {
                $this->error('已存在（加 --force 覆盖）：' . $path);
                return;
            }
        }

        foreach ($files as $path => $contents) {
            $dir = dirname($path);
            if (! is_dir($dir) && ! mkdir($dir, 0755, true) && ! is_dir($dir)) {
                $this->error('无法创建目录：' . $dir);
                return;
            }
            file_put_contents($path, $contents);
            $this->line('created ' . $this->relative($path));
        }

        if (! $this->input->getOption('no-register')) {
            if ($this->register($exchange, $queueClass)) {
                $this->line('registered ' . $exchange . ' => ' . $queueClass . '::class');
            }
        }

        $this->info('完成。重启后 account-sharded-consume 会自动拉起该队列。');
        $this->line(sprintf('发送：\\App\\Amqp\\Queue\\%s::produce($data);', $queueClass));
        $this->line(sprintf('运维：php bin/hyperf.php amqp:dlq-info %s', $exchange));
    }

    /**
     * @return array{0: string, 1: string} [StudlyName, exchange]
     */
    private function normalizeName(string $raw): array
    {
        $raw = trim($raw);
        $raw = (string) preg_replace('#(Queue|Producer|Consumer)$#i', '', $raw);
        $raw = str_replace(['-', '.', '/'], '_', $raw);
        $raw = (string) preg_replace('#[^A-Za-z0-9_]#', '', $raw);

        if ($raw === '') {
            return ['', ''];
        }

        if (str_contains($raw, '_')) {
            $parts = array_filter(explode('_', strtolower($raw)), static fn ($p) => $p !== '');
            $studly = implode('', array_map(static fn ($p) => ucfirst($p), $parts));
        } else {
            // accountRefund / AccountRefund → AccountRefund
            $studly = ucfirst($raw);
        }

        if ($studly === '') {
            return ['', ''];
        }

        return [$studly, lcfirst($studly)];
    }

    private function register(string $exchange, string $queueClass): bool
    {
        $registryPath = BASE_PATH . '/app/Amqp/AccountQueueRegistry.php';
        $code = file_get_contents($registryPath);
        if ($code === false) {
            $this->error('无法读取 AccountQueueRegistry');
            return false;
        }

        if (str_contains($code, "'" . $exchange . "'")) {
            $this->warn('Registry 已包含 ' . $exchange . '，跳过注册');
            return false;
        }

        $useLine = "use App\\Amqp\\Queue\\{$queueClass};\n";
        if (! str_contains($code, $useLine) && ! str_contains($code, "use App\\Amqp\\Queue\\{$queueClass};")) {
            // 插到最后一个 use App\Amqp\Queue\ 之后；没有则插到 namespace 后第一处 use 前
            if (preg_match('#(use App\\\\Amqp\\\\Queue\\\\[^;]+;\n)(?!use App\\\\Amqp\\\\Queue\\\\)#', $code, $m, PREG_OFFSET_CAPTURE)) {
                $pos = $m[0][1] + strlen($m[0][0]);
                $code = substr($code, 0, $pos) . $useLine . substr($code, $pos);
            } elseif (preg_match('#namespace App\\\\Amqp;\n\n#', $code, $m, PREG_OFFSET_CAPTURE)) {
                $pos = $m[0][1] + strlen($m[0][0]);
                $code = substr($code, 0, $pos) . $useLine . substr($code, $pos);
            }
        }

        $entry = "            '{$exchange}' => {$queueClass}::class,\n";
        $updated = preg_replace(
            '#(return\s*\[\n)#',
            '$1' . $entry,
            $code,
            1,
            $count
        );

        if (! $count || $updated === null) {
            $this->error('无法自动写入 Registry，请手动添加：' . trim($entry));
            return false;
        }

        file_put_contents($registryPath, $updated);

        return true;
    }

    private function relative(string $path): string
    {
        return str_starts_with($path, BASE_PATH . '/')
            ? substr($path, strlen(BASE_PATH) + 1)
            : $path;
    }

    /**
     * @return list<string>
     */
    private function parseSerialFields(string $raw): array
    {
        $fields = [];
        foreach (explode(',', $raw) as $field) {
            $field = trim($field);
            if ($field === '') {
                continue;
            }
            $fields[] = $field;
        }

        return array_values(array_unique($fields));
    }

    /**
     * @param list<string> $serialFields
     */
    private function queueStub(
        string $queueClass,
        string $producerClass,
        string $consumerClass,
        string $exchange,
        string $label,
        array $serialFields
    ): string {
        $serialLabel = implode(' + ', $serialFields);
        $serialMethod = '';
        $isDefault = $serialFields === ['tenant_id', 'busine_id'];
        if (! $isDefault) {
            $export = implode(",\n            ", array_map(
                static fn (string $f) => "'" . addslashes($f) . "'",
                $serialFields
            ));
            $serialMethod = <<<PHP

    public static function serialFields(): array
    {
        return [
            {$export},
        ];
    }

PHP;
        }

        return <<<PHP
<?php

declare(strict_types=1);

namespace App\\Amqp\\Queue;

use App\\Amqp\\Consumer\\{$consumerClass};
use App\\Amqp\\Producer\\{$producerClass};
use App\\Amqp\\Sharded\\TenantBusinessShardedQueue;

/** {$label}：同一 {$serialLabel} 串行，不同组合可并行。 */
final class {$queueClass} extends TenantBusinessShardedQueue
{
    public const EXCHANGE = '{$exchange}';
{$serialMethod}
    public static function consumerClass(): string
    {
        return {$consumerClass}::class;
    }

    public static function producerClass(): string
    {
        return {$producerClass}::class;
    }
}

PHP;
    }

    private function producerStub(string $queueClass, string $producerClass): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace App\\Amqp\\Producer;

use App\\Amqp\\Queue\\{$queueClass};
use App\\Amqp\\Sharded\\ShardedProducer;
use Hyperf\\Amqp\\Annotation\\Producer;

#[Producer(exchange: {$queueClass}::EXCHANGE, routingKey: '')]
class {$producerClass} extends ShardedProducer
{
    protected static function queueClass(): string
    {
        return {$queueClass}::class;
    }
}

PHP;
    }

    private function consumerStub(string $queueClass, string $consumerClass, string $label): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace App\\Amqp\\Consumer;

use App\\Amqp\\Queue\\{$queueClass};
use App\\Amqp\\Sharded\\ShardedConsumer;
use Hyperf\\Amqp\\Result;
use PhpAmqpLib\\Message\\AMQPMessage;

class {$consumerClass} extends ShardedConsumer
{
    protected function handle(mixed \$data, AMQPMessage \$message): Result
    {
        // TODO: 业务处理

        return Result::ACK;
    }

    protected static function queueClass(): string
    {
        return {$queueClass}::class;
    }
}

PHP;
    }
}
