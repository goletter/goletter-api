<?php

declare(strict_types=1);

namespace App\Callback\Handler;

use App\Callback\Contract\CallbackHandlerInterface;

use function Goletter\Utils\logging;

final class DemoPingHandler implements CallbackHandlerInterface
{
    public function handle(string $event, array $payload): mixed
    {
        logging([
            'event' => $event,
            'payload' => $payload,
        ], '入站回调 ping', 'callback');

        return [
            'pong' => true,
            'at' => date('c'),
        ];
    }
}
