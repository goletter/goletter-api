<?php

declare(strict_types=1);

namespace App\Amqp\Consumer;

use Hyperf\Amqp\Result;
use Hyperf\Amqp\Annotation\Consumer;
use Hyperf\Amqp\Message\ConsumerMessage;
use PhpAmqpLib\Message\AMQPMessage;
use function Goletter\Utils\logging;

#[Consumer(exchange: 'account', routingKey: 'account', queue: 'account', name: "AccountConsumer", nums: 1)]
class AccountConsumer extends ConsumerMessage
{
    public function consumeMessage($data, AMQPMessage $message): Result
    {
        logging([...$data], '账户消息队列', 'test');

        return Result::ACK;
    }
}
