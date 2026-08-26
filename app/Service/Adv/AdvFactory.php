<?php

declare(strict_types=1);

namespace App\Service\Adv;

use App\Service\Adv\Facebook\FacebookService;
use Hyperf\Collection\Arr;

class AdvFactory
{
    /**
     * 简单广告平台工厂
     *
     * @param int|string $type 平台标识：0/facebook、1/tiktok、2/google、3/akm
     * @param string|null $token 平台授权 token（有些平台需要）
     * @return object
     */
    public static function make(int|string $type, ?string $token = null, int|string $busineId = 0, int|string $platformId = 0): object
    {
        if (is_string($type)) {
            $platform = strtolower($type);
        } else {
            $platform = (string) Arr::get(['facebook', 'tiktok', 'google'], $type, '');
        }

        return match ($platform) {
            'facebook', 'fb', '0' => new FacebookService((string) $token, $busineId, $platformId),
            default => throw new \InvalidArgumentException(sprintf('Unsupported adv platform: %s', $platform)),
        };
    }
}

