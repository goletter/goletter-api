<?php

declare(strict_types=1);

namespace App\Listener;

use App\Log\Channels;
use Goletter\Amqp\Contract\QueueRegistryInterface;
use Goletter\Amqp\Sharded\ShardedQueue;
use Hyperf\Event\Annotation\Listener;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Framework\Event\BeforeMainServerStart;
use Hyperf\Logger\LoggerFactory;
use PhpAmqpLib\Exception\AMQPConnectionClosedException;
use PhpAmqpLib\Exception\AMQPIOException;
use PhpAmqpLib\Exception\AMQPSocketException;
use PhpAmqpLib\Exception\AMQPTimeoutException;
use Psr\Log\LoggerInterface;
use Throwable;

use function Hyperf\Support\env;

/**
 * 主进程 fork 前声明分片队列拓扑（DLX/DLQ + 全部分片）。
 *
 * 消费者进程只会声明自己消费的队列；此处一次性建好死信与分片，避免发版窗口内
 * 消息进 DLX 却因留证队列不存在被 broker 丢弃。
 *
 * QUEUE_DECLARE_ON_BOOT=false 时跳过，改由部署脚本声明。
 * broker 不可达：打日志告警，不阻断 HTTP 启动；其它声明错误 fail-fast。
 */
#[Listener]
class QueueTopologyListener implements ListenerInterface
{
    private LoggerInterface $logger;

    public function __construct(
        private QueueRegistryInterface $registry,
        LoggerFactory $loggerFactory,
    ) {
        $this->logger = $loggerFactory->get(Channels::QUEUE);
    }

    public function listen(): array
    {
        return [BeforeMainServerStart::class];
    }

    public function process(object $event): void
    {
        if (! filter_var(env('QUEUE_DECLARE_ON_BOOT', true), FILTER_VALIDATE_BOOLEAN)) {
            return;
        }

        $classes = $this->registry->classes();
        $shardTotal = 0;

        try {
            foreach ($classes as $queueClass) {
                if (! is_a($queueClass, ShardedQueue::class, true)) {
                    throw new \InvalidArgumentException($queueClass . ' 必须继承 ' . ShardedQueue::class);
                }

                for ($shard = 0, $n = $queueClass::shards(); $shard < $n; ++$shard) {
                    $queueClass::declareShard($shard);
                    ++$shardTotal;
                }
            }
        } catch (Throwable $exception) {
            $context = [
                'error_type' => $exception::class,
                'error_message' => $exception->getMessage(),
                'queue_count' => count($classes),
            ];

            if ($this->isBrokerUnavailable($exception)) {
                $this->logger->warning('queue_topology_broker_unavailable', $context);
                fwrite(STDERR, sprintf(
                    "[queue] broker 不可达，跳过拓扑声明：%s\n",
                    $exception->getMessage()
                ));

                return;
            }

            $this->logger->error('queue_topology_declare_failed', $context);
            fwrite(STDERR, sprintf(
                "[queue] 拓扑声明失败，服务拒绝启动：%s\n",
                $exception->getMessage()
            ));

            throw $exception;
        }

        $this->logger->info('queue_topology_declared', [
            'queue_count' => count($classes),
            'shard_total' => $shardTotal,
        ]);
    }

    private function isBrokerUnavailable(Throwable $exception): bool
    {
        if ($exception instanceof AMQPIOException
            || $exception instanceof AMQPTimeoutException
            || $exception instanceof AMQPConnectionClosedException
            || $exception instanceof AMQPSocketException
        ) {
            return true;
        }

        $message = strtolower($exception->getMessage());

        return str_contains($message, 'connection refused')
            || str_contains($message, 'timed out')
            || str_contains($message, 'connection reset')
            || str_contains($message, 'broken pipe');
    }
}
