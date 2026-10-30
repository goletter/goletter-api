<?php

declare(strict_types=1);

namespace App\Service\Adv\Akm;

use Goletter\Akm\AkmClient;

/**
 * 蓝瀚 广告平台聚合服务
 *
 * 负责将 token 传递给具体的 API 类（账户、报表等），
 * 对外提供统一入口。
 */
class AkmService
{
    protected AkmAccountApi $accountApi;

    public function __construct(string $token)
    {
        $client = new AkmClient($token);
        $this->accountApi = new AkmAccountApi($client);
    }

    /**
     * 账户相关能力
     */
    public function account(): AkmAccountApi
    {
        return $this->accountApi;
    }
}