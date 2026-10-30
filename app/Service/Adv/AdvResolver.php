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

namespace App\Service\Adv;

use App\Service\Adv\Facebook\FacebookService;
use Hyperf\Collection\Arr;
use InvalidArgumentException;

class AdvResolver
{
    /**
     * 根据账户信息解析对应平台的广告服务实例.
     */
    public function resolveAdv(
        int|string $type,
        ?string $token = null,
        int|string $busineId = 0,
        int|string $platformId = 0
    ): ?object {
        if (is_string($type)) {
            $platform = strtolower($type);
        } else {
            $platform = (string) Arr::get(['facebook', 'tiktok', 'google'], $type, '');
        }

        return match ($platform) {
            'facebook', 'fb', '0' => new FacebookService((string) $token, $busineId, $platformId),
            default => throw new InvalidArgumentException(sprintf('Unsupported adv platform: %s', $platform)),
        };
    }
}
