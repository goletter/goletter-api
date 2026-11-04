<?php

declare(strict_types=1);

namespace App\Callback\Contract;

interface CallbackHandlerInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function handle(string $event, array $payload): mixed;
}
