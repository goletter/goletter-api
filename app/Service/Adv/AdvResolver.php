<?php

declare(strict_types=1);

namespace App\Service\Adv;

class AdvResolver
{
    /**
     * 根据账户信息解析对应平台的广告服务实例
     */
    public function resolveAdv(string $token = '', string $type = '', $isOauth = false, $busineId = 0): ?object
    {
        $platformId = 0;

        return AdvFactory::make($type, (string) $token, $busineId, $platformId);
    }
}