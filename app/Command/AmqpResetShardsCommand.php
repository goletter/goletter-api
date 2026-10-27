<?php

declare(strict_types=1);

namespace App\Command;

use App\Amqp\AccountQueueRegistry;
use App\Amqp\Sharded\ShardedQueue;
use Hyperf\Amqp\ConnectionFactory;
use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use PhpAmqpLib\Channel\AMQPChannel;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Throwable;

use function Goletter\Utils\di;

/**
 * 删除旧分片队列以便按新参数（含 DLX）重建。
 *
 * RabbitMQ 不允许修改已有队列的 x-dead-letter-*，改代码后若出现
 * PRECONDITION_FAILED inequivalent arg 'x-dead-letter-exchange'，执行本命令。
 *
 *   php bin/hyperf.php amqp:reset-shards accountRecharge --force
 *   php bin/hyperf.php amqp:reset-shards --all --force
 */
#[Command]
class AmqpResetShardsCommand extends HyperfCommand
{
    public function __construct()
    {
        parent::__construct('amqp:reset-shards');
    }

    public function configure(): void
    {
        parent::configure();
        $this->setDescription('删除分片队列（保留死信），以便用新参数重建');
        $this->addArgument('name', InputArgument::OPTIONAL, '业务名，如 accountRecharge');
        $this->addOption('all', null, InputOption::VALUE_NONE, '重置注册表内全部业务');
        $this->addOption('force', 'f', InputOption::VALUE_NONE, '确认删除（必填，防止误删）');
        $this->addOption('purge-dlq', null, InputOption::VALUE_NONE, '同时删除死信队列');
    }

    public function handle()
    {
        if (! $this->input->getOption('force')) {
            $this->error('危险操作：会删除分片队列中未消费消息。确认后请加 --force');
            $this->line('示例：php bin/hyperf.php amqp:reset-shards --all --force');
            return;
        }

        $name = $this->input->getArgument('name');
        $queues = $this->resolveQueues($name === null ? null : (string) $name);
        if ($queues === []) {
            $this->error('未知业务。可选：' . implode(', ', array_keys(AccountQueueRegistry::all())));
            return;
        }

        $factory = di()->get(ConnectionFactory::class);
        $purgeDlq = (bool) $this->input->getOption('purge-dlq');

        foreach ($queues as $alias => $queueClass) {
            $this->info('重置：' . $alias);

            $connection = $factory->getConnection('default');
            $channel = $connection->getChannel();
            try {
                for ($shard = 0; $shard < $queueClass::shards(); ++$shard) {
                    $queue = $queueClass::queue($shard);
                    $channel = $this->deleteQueue($connection, $channel, $queue);
                }

                if ($purgeDlq) {
                    $channel = $this->deleteQueue($connection, $channel, $queueClass::dlq());
                }

                // 重建 DLX/DLQ；业务分片由消费进程 / produce 时 declareShard 重建
                $connection->releaseChannel($channel);
                $queueClass::declareDeadLetter();
                $channel = $connection->getChannel();
                $this->line('  declared ' . $queueClass::dlxExchange() . ' + ' . $queueClass::dlq());
            } finally {
                try {
                    $connection->releaseChannel($channel);
                } catch (Throwable) {
                }
            }
        }

        $this->info('完成。请重启服务，分片队列会按带 DLX 的新定义自动创建。');
    }

    /**
     * @return array<string, class-string<ShardedQueue>>
     */
    private function resolveQueues(?string $name): array
    {
        $all = AccountQueueRegistry::all();
        if ($name === null || $name === '' || $this->input->getOption('all')) {
            return $all;
        }

        $queueClass = AccountQueueRegistry::get($name);
        if ($queueClass === null) {
            return [];
        }

        return [$name => $queueClass];
    }

    private function deleteQueue(mixed $connection, AMQPChannel $channel, string $queue): AMQPChannel
    {
        try {
            $channel->queue_delete($queue, false, false, false);
            $this->line('  deleted ' . $queue);
            return $channel;
        } catch (Throwable $e) {
            $this->warn(sprintf('  skip %s：%s', $queue, $e->getMessage()));
            try {
                $channel->close();
            } catch (Throwable) {
            }

            return $connection->getChannel();
        }
    }
}
