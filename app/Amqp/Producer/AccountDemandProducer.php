<?php

declare(strict_types=1);

namespace App\Amqp\Producer;

use App\Amqp\AccountDemandQueue;
use Hyperf\Amqp\Annotation\Producer;
use Hyperf\Amqp\Message\ProducerMessage;

#[Producer(exchange: AccountDemandQueue::EXCHANGE, routingKey: '')]
class AccountDemandProducer extends ProducerMessage
{
    public function __construct(array $data)
    {
        $this->payload = $data;
        $this->setExchange(AccountDemandQueue::EXCHANGE);
        $this->setRoutingKey(AccountDemandQueue::routingKey(AccountDemandQueue::shard($data)));
    }
}
