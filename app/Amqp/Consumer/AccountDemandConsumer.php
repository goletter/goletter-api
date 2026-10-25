<?php

declare(strict_types=1);

namespace App\Amqp\Consumer;

use Hyperf\Amqp\Annotation\Consumer;
use Hyperf\Amqp\Message\ConsumerMessage;
use Hyperf\Amqp\Message\ProducerDelayedMessageTrait;
use Hyperf\Amqp\Message\Type;
use Hyperf\Amqp\Result;
use PhpAmqpLib\Message\AMQPMessage;

use function Goletter\Utils\logging;

#[Consumer(exchange: 'accountDemand', routingKey: 'accountDemand', queue: 'accountDemand', name: 'AccountDemandConsumer', nums: 1)]
class AccountDemandConsumer extends ConsumerMessage
{
    /** 声明 x-delayed-message exchange，与 Producer 一致 */
    use ProducerDelayedMessageTrait;

    protected string|Type $type = Type::TOPIC;

    protected ?array $qos = [
        'prefetch_size' => 0,
        'prefetch_count' => 1,
        'global' => false,
    ];

    public function consumeMessage($data, AMQPMessage $message): Result
    {
        logging([...$data], '测试消息队列', 'test');

        return Result::ACK;
    }
}
