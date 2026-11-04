<?php

declare(strict_types=1);

namespace App\Notifier\Contract;

interface OutboundHandlerInterface
{
    /**
     * @param mixed $context 多为 Model / array
     */
    public function handle(string $event, mixed $context): bool;
}
