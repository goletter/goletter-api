<?php

declare(strict_types=1);

namespace App\Amqp\Consumer;

use Hyperf\Amqp\Annotation\Consumer;
use Hyperf\Amqp\Message\ConsumerMessage;
use Hyperf\Amqp\Result;
use PhpAmqpLib\Message\AMQPMessage;

use function Goletter\Utils\logging;

#[Consumer(exchange: 'accountDemand', routingKey: 'accountDemand', queue: 'accountDemand', name: 'AccountDemandConsumer', nums: 10)]
class AccountDemandConsumer extends ConsumerMessage
{
    public function consumeMessage($data, AMQPMessage $message): Result
    {
        logging([...$data], '账户需求消息队列', 'test');

        return Result::ACK;
    }
}
