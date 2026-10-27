<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */
use Hyperf\Amqp\Message\ProducerMessage;
use Hyperf\Amqp\Producer;

use function Goletter\Utils\di;

/**
 * 推送消息到 AMQP.
 */
function produce(ProducerMessage $message): bool
{
    return di()->get(Producer::class)->produce($message);
}
