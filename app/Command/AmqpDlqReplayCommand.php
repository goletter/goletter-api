<?php

declare(strict_types=1);

namespace App\Command;

use App\Amqp\AccountQueueRegistry;
use App\Amqp\Sharded\ShardedProducer;
use App\Amqp\Sharded\ShardedQueue;
use Hyperf\Amqp\ConnectionFactory;
use Hyperf\Amqp\Packer\Packer;
use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use PhpAmqpLib\Message\AMQPMessage;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Throwable;

use function Goletter\Utils\di;

/**
 * 将死信消息重投回业务分片队列（重置 attempts）。
 *
 *   php bin/hyperf.php amqp:dlq-replay accountRecharge --limit=10
 *   php bin/hyperf.php amqp:dlq-replay accountRecharge --dry-run
 */
#[Command]
class AmqpDlqReplayCommand extends HyperfCommand
{
    public function __construct()
    {
        parent::__construct('amqp:dlq-replay');
    }

    public function configure(): void
    {
        parent::configure();
        $this->setDescription('从死信队列重投消息到业务分片队列');
        $this->addArgument('name', InputArgument::REQUIRED, '业务名，如 accountRecharge');
        $this->addOption('limit', null, InputOption::VALUE_REQUIRED, '最多重投条数', '10');
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, '只打印不真正投递/ACK');
    }

    public function handle()
    {
        $name = (string) $this->input->getArgument('name');
        $queueClass = AccountQueueRegistry::get($name);
        if ($queueClass === null || ! is_a($queueClass, ShardedQueue::class, true)) {
            $this->error('未知业务。可选：' . implode(', ', array_keys(AccountQueueRegistry::all())));
            return;
        }

        $producerClass = $queueClass::producerClass();
        if (! is_a($producerClass, ShardedProducer::class, true)) {
            $this->error($producerClass . ' 必须继承 ShardedProducer');
            return;
        }

        $limit = max(1, (int) $this->input->getOption('limit'));
        $dryRun = (bool) $this->input->getOption('dry-run');

        $queueClass::declareDeadLetter();

        $factory = di()->get(ConnectionFactory::class);
        $packer = di()->get(Packer::class);
        $connection = $factory->getConnection('default');
        $channel = $connection->getChannel();

        $ok = 0;
        $fail = 0;

        try {
            /** @var list<AMQPMessage> $batch */
            $batch = [];
            for ($i = 0; $i < $limit; ++$i) {
                $message = $channel->basic_get($queueClass::dlq(), false);
                if ($message === null) {
                    break;
                }
                $batch[] = $message;
            }

            foreach ($batch as $index => $message) {
                try {
                    $data = $packer->unpack($message->getBody());
                    if (! is_array($data)) {
                        throw new \RuntimeException('payload 不是数组，无法按分片重投');
                    }

                    // 重投给予完整重试预算
                    unset($data['attempts']);

                    $shard = $queueClass::shard($data);
                    $this->line(sprintf(
                        '#%d key=%s -> %s %s',
                        $index + 1,
                        $queueClass::serialKey($data),
                        $queueClass::queue($shard),
                        $dryRun ? '[dry-run]' : ''
                    ));

                    if ($dryRun) {
                        $channel->basic_nack($message->getDeliveryTag(), false, true);
                    } else {
                        produce(new $producerClass($data));
                        $channel->basic_ack($message->getDeliveryTag());
                    }
                    ++$ok;
                } catch (Throwable $e) {
                    ++$fail;
                    $this->error('重投失败：' . $e->getMessage());
                    $channel->basic_nack($message->getDeliveryTag(), false, true);
                }
            }
        } finally {
            $connection->releaseChannel($channel);
        }

        $this->info(sprintf(
            '完成：成功 %d，失败 %d%s',
            $ok,
            $fail,
            $dryRun ? '（dry-run 未真正投递）' : ''
        ));
    }
}
