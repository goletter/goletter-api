<?php

declare(strict_types=1);

namespace App\Amqp\Consumer;

use Hyperf\Amqp\Annotation\Consumer;
use Hyperf\Amqp\Builder\QueueBuilder;
use Hyperf\Amqp\Message\ConsumerMessage;
use Hyperf\Amqp\Message\Type;
use Hyperf\Amqp\Result;
use PhpAmqpLib\Message\AMQPMessage;

use function Goletter\Utils\logging;

#[Consumer(exchange: 'accountDemand', routingKey: 'accountDemand', queue: 'accountDemand', name: 'AccountDemandConsumer', nums: 1)]
class AccountDemandConsumer extends ConsumerMessage
{
    protected string|Type $type = Type::TOPIC;

    protected ?array $qos = [
        'prefetch_size' => 0,
        'prefetch_count' => 1,
        'global' => false,
    ];

    /**
     * RabbitMQ 4.4 的延迟投递只在 quorum queue 上生效。
     */
    public function getQueueBuilder(): QueueBuilder
    {
        return (new QueueBuilder())
            ->setQueue($this->getQueue())
            ->setArguments(['x-queue-type' => ['S', 'quorum']]);
    }

    public function consumeMessage($data, AMQPMessage $message): Result
    {
        logging([...$data], '测试消息队列', 'test');

        return Result::ACK;
    }
}
