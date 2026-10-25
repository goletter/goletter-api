<?php

declare(strict_types=1);
use function Goletter\Utils\di;

/**
 * 推送消息到 AMQP.
 * @param \Hyperf\Amqp\Message\ProducerMessage $message
 * @return bool
 */
function produce(\Hyperf\Amqp\Message\ProducerMessage $message): bool
{
    return di()->get(\Hyperf\Amqp\Producer::class)->produce($message);
}