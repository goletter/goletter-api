<?php

declare(strict_types=1);

namespace App\Service\Adv;

class AdvResolver
{
    public function resolveAdv(string $token = '', string $type = '', $busineId = 0): ?object
    {
        $platformId = 0;

        return AdvFactory::make($type, (string) $token, $busineId, $platformId);
    }
}