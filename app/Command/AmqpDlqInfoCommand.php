<?php

declare(strict_types=1);

namespace App\Command;

use App\Amqp\AccountQueueRegistry;
use App\Amqp\Sharded\ShardedQueue;
use Hyperf\Amqp\ConnectionFactory;
use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

use function Goletter\Utils\di;
use function Goletter\Utils\logging;

/**
 * 查看死信积压（可挂 crontab 做定时告警）。
 *
 *   php bin/hyperf.php amqp:dlq-info
 *   php bin/hyperf.php amqp:dlq-info accountRecharge
 *   php bin/hyperf.php amqp:dlq-info --all
 */
#[Command]
class AmqpDlqInfoCommand extends HyperfCommand
{
    public function __construct()
    {
        parent::__construct('amqp:dlq-info');
    }

    public function configure(): void
    {
        parent::configure();
        $this->setDescription('查看分片业务死信队列积压');
        $this->addArgument('name', InputArgument::OPTIONAL, '业务名，如 accountRecharge；省略则看全部');
        $this->addOption('all', null, InputOption::VALUE_NONE, '检查注册表内全部队列');
    }

    public function handle()
    {
        $name = $this->input->getArgument('name');
        $queues = $this->resolveQueues($name === null ? null : (string) $name);
        if ($queues === []) {
            $this->error('未知业务。可选：' . implode(', ', array_keys(AccountQueueRegistry::all())));
            return;
        }

        $factory = di()->get(ConnectionFactory::class);
        $totalReady = 0;

        foreach ($queues as $alias => $queueClass) {
            $queueClass::declareDeadLetter();

            $connection = $factory->getConnection('default');
            $channel = $connection->getChannel();
            try {
                [, $messageCount, $consumerCount] = $channel->queue_declare(
                    $queueClass::dlq(),
                    true,
                    true,
                    false,
                    false,
                    false,
                    [],
                    null
                );
            } finally {
                $connection->releaseChannel($channel);
            }

            $messageCount = (int) $messageCount;
            $consumerCount = (int) $consumerCount;
            $totalReady += $messageCount;

            $this->line(sprintf(
                '%s (%s) ready=%d consumers=%d',
                $alias,
                $queueClass::dlq(),
                $messageCount,
                $consumerCount
            ));

            if ($messageCount > 0) {
                logging([
                    'event' => 'dlq_backlog',
                    'alias' => $alias,
                    'queue' => $queueClass::dlq(),
                    'ready' => $messageCount,
                    'consumers' => $consumerCount,
                    // 这里可接企微/钉钉告警
                ], '分片队列死信积压', 'queue');
            }
        }

        if ($totalReady > 0) {
            $this->warn(sprintf('合计死信积压 %d 条，可用 amqp:dlq-replay 重投', $totalReady));
        } else {
            $this->info('无死信积压');
        }
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
}
