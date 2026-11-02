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

use Goletter\Amqp\Command\AmqpDlqInfoCommand;
use Goletter\Amqp\Command\AmqpDlqPurgeCommand;
use Goletter\Amqp\Command\AmqpDlqReplayCommand;
use Goletter\Amqp\Command\AmqpMakeQueueCommand;
use Goletter\Amqp\Command\AmqpPurgeCommand;
use Goletter\Amqp\Command\AmqpResetShardsCommand;

return [
    AmqpDlqInfoCommand::class,
    AmqpDlqPurgeCommand::class,
    AmqpDlqReplayCommand::class,
    AmqpMakeQueueCommand::class,
    AmqpPurgeCommand::class,
    AmqpResetShardsCommand::class,
];
