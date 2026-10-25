<?php

declare(strict_types=1);

namespace App\Amqp\Producer;

use Hyperf\Amqp\Annotation\Producer;
use Hyperf\Amqp\Message\ProducerDelayedMessageTrait;
use Hyperf\Amqp\Message\ProducerMessage;
use Hyperf\Amqp\Message\Type;

#[Producer(exchange: 'accountDemand', routingKey: 'accountDemand')]
class AccountDemandProducer extends ProducerMessage
{
    use ProducerDelayedMessageTrait;

    /** 对应 x-delayed-type，需与 Consumer 一致 */
    protected string|Type $type = Type::TOPIC;

    public function __construct($data)
    {
        $this->payload = $data;
    }
}
