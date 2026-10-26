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

namespace App\Amqp\Sharded;

use Hyperf\Amqp\Consumer;
use Hyperf\Coroutine\Coroutine;
use Hyperf\Process\AbstractProcess;
use Hyperf\Process\ProcessManager;
use InvalidArgumentException;
use Throwable;

/**
 * 为每个分片起一个协程消费。
 *
 * 子类实现 queueClass() / consumerClass()，并加 #[Process]。
 */
abstract class ShardedConsumeProcess extends AbstractProcess
{
    public function handle(): void
    {
        $queueClass = $this->queueClass();
        $consumerClass = $this->consumerClass();
        if (! is_a($queueClass, ShardedQueue::class, true)) {
            throw new InvalidArgumentException($queueClass . ' 必须继承 ' . ShardedQueue::class);
        }
        if (! is_a($consumerClass, ShardedConsumer::class, true)) {
            throw new InvalidArgumentException($consumerClass . ' 必须继承 ' . ShardedConsumer::class);
        }

        $consumer = $this->container->get(Consumer::class);

        for ($shard = 0; $shard < $queueClass::shards(); ++$shard) {
            Coroutine::create(function () use ($consumer, $consumerClass, $shard) {
                while (ProcessManager::isRunning()) {
                    try {
                        $consumer->consume(new $consumerClass($shard));
                    } catch (Throwable $e) {
                        $this->logThrowable($e);
                    }

                    if (! ProcessManager::isRunning()) {
                        return;
                    }

                    sleep(1);
                }
            });
        }

        while (ProcessManager::isRunning()) {
            sleep(1);
        }
    }

    /**
     * @return class-string<ShardedQueue>
     */
    abstract protected function queueClass(): string;

    /**
     * @return class-string<ShardedConsumer>
     */
    abstract protected function consumerClass(): string;
}
