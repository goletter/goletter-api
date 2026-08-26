<?php

declare(strict_types=1);

namespace App\Service\Adv\Facebook;

use Goletter\Adv\Platforms\Facebook\FacebookClient;

/**
 * Facebook 广告平台聚合服务
 *
 * 负责将 token 传递给具体的 API 类（账户、报表等），
 * 对外提供统一入口。
 */
class FacebookService
{
    protected FacebookAccountApi $accountApi;
    protected FacebookReportApi $reportApi;

    protected FacebookBusinessApi $businessApi;

    public function __construct(string $token, int $busineId = 0, int $platformId = 0)
    {
        $client = new FacebookClient($token, $busineId, $platformId);
        $this->accountApi = new FacebookAccountApi($client);
        $this->reportApi = new FacebookReportApi($client);
        $this->businessApi = new FacebookBusinessApi($client);
    }

    /**
     * 账户相关能力
     */
    public function account(): FacebookAccountApi
    {
        return $this->accountApi;
    }

    /**
     * 报表相关能力
     */
    public function report(): FacebookReportApi
    {
        return $this->reportApi;
    }

    /**
     * Bm
     * @return FacebookBusinessApi
     */
    public function business(): FacebookBusinessApi
    {
        return $this->businessApi;
    }
}

