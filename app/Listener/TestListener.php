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

namespace App\Listener;

use App\Event\TestEvent;
use Hyperf\Event\Annotation\Listener;
use Hyperf\Event\Contract\ListenerInterface;

#[Listener]
class TestListener implements ListenerInterface
{
    public function listen(): array
    {
        return [
            TestEvent::class,
        ];
    }

    public function process(object $event): void
    {
        logging([], '333', 'test');
    }
}
