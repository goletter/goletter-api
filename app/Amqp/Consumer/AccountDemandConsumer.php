<?php

declare(strict_types=1);

namespace App\Amqp\Consumer;

use App\Amqp\AccountDemandQueue;
use Hyperf\Amqp\Builder\QueueBuilder;
use Hyperf\Amqp\Message\ConsumerMessage;
use Hyperf\Amqp\Message\Type;
use Hyperf\Amqp\Result;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPAbstractCollection;
use PhpAmqpLib\Wire\AMQPTable;

use function Goletter\Utils\logging;

class AccountDemandConsumer extends ConsumerMessage
{
    protected string|Type $type = Type::TOPIC;

    protected ?array $qos = [
        'prefetch_size' => 0,
        'prefetch_count' => 1,
        'global' => false,
    ];

    public function __construct(private int $shard)
    {
        $this->setExchange(AccountDemandQueue::EXCHANGE);
        $this->setRoutingKey(AccountDemandQueue::routingKey($shard));
        $this->setQueue(AccountDemandQueue::queue($shard));
    }

    public function getQueueBuilder(): QueueBuilder
    {
        $arguments = new AMQPTable();
        $arguments->set('x-queue-type', 'quorum');
        // 多台机器同时启动时，同一分片只有一个消费者在干活
        $arguments->set('x-single-active-consumer', true, AMQPAbstractCollection::T_BOOL);

        return (new QueueBuilder())
            ->setQueue($this->getQueue())
            ->setArguments($arguments);
    }

    public function consumeMessage($data, AMQPMessage $message): Result
    {
        $payload = is_array($data) ? $data : [];
        logging([
            'shard' => $this->shard,
            'key' => AccountDemandQueue::serialKey($payload),
            ...$payload,
        ], '账户需求消息队列', 'test');

        return Result::ACK;
    }
}
