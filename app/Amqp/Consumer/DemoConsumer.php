<?php

declare(strict_types=1);

namespace App\Amqp\Consumer;

use Hyperf\Amqp\Annotation\Consumer;
use Hyperf\Amqp\Message\ConsumerMessage;
use Hyperf\Amqp\Result;
use Hyperf\Redis\Redis;
use PhpAmqpLib\Message\AMQPMessage;

use function Goletter\Utils\di;
use function Goletter\Utils\logging;

#[Consumer(exchange: 'hyperf', routingKey: 'hyperf', queue: 'hyperf', name: 'DemoConsumer', nums: 1)]
class DemoConsumer extends ConsumerMessage
{
    public function consumeMessage($data, AMQPMessage $message): Result
    {
        $id = (string) ($data['id'] ?? md5(json_encode($data)));
        $key = 'amqp:demo:attempt:' . $id;

        /** @var Redis $redis */
        $redis = \Goletter\Utils\redis();
        $attempt = $redis->incr($key);
        $redis->expire($key, 120);

        if ($attempt === 1) {
            logging([
                'id' => $id,
                'attempt' => $attempt,
                'result' => 'fail_then_requeue',
                'data' => $data,
            ], 'DemoConsumer', 'amqp');

            // 第一次失败，重新入队，下一次再消费
            return Result::REQUEUE;
        }

        logging([
            'id' => $id,
            'attempt' => $attempt,
            'result' => 'success',
            'data' => $data,
        ], 'DemoConsumer', 'amqp');

        $redis->del($key);

        return Result::ACK;
    }
}
