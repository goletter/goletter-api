<?php

declare(strict_types=1);

namespace App\Amqp\Producer;

use Hyperf\Amqp\Annotation\Producer;
use Hyperf\Amqp\Message\ProducerMessage;
use Hyperf\Amqp\Message\Type;
use PhpAmqpLib\Wire\AMQPAbstractCollection;
use PhpAmqpLib\Wire\AMQPTable;

#[Producer(exchange: 'accountDemand', routingKey: 'accountDemand')]
class AccountDemandProducer extends ProducerMessage
{
    protected string|Type $type = Type::TOPIC;

    public function __construct($data)
    {
        $this->payload = $data;
    }

    /**
     * RabbitMQ 4.4 quorum queue：x-opt-delivery-time 是到期的 Unix 毫秒时间戳（64 位整数）。
     * 相对延迟写 x-delay 不会生效。
     */
    public function setDelayMs(int $millisecond): static
    {
        $due = (int) floor(microtime(true) * 1000) + max(0, $millisecond);
        $headers = new AMQPTable();
        $headers->set('x-opt-delivery-time', $due, AMQPAbstractCollection::T_INT_LONGLONG);
        $this->properties['application_headers'] = $headers;

        return $this;
    }
}
