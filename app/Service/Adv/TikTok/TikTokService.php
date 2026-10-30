<?php

declare(strict_types=1);

namespace App\Service\Adv\TikTok;

use Goletter\Adv\Platforms\TikTok\TikTokClient;

/**
 * TikTok 广告平台聚合服务
 *
 * 目前只是简单封装账户 / 报表 API，并预留 token 传递。
 */
class TikTokService
{
    protected TikTokAccountApi $accountApi;
    protected TikTokReportApi $reportApi;

    public function __construct(string $token = '')
    {
        $client = new TikTokClient($token);
        $this->accountApi = new TikTokAccountApi($client);
        $this->reportApi = new TikTokReportApi($client);
    }

    public function account(): TikTokAccountApi
    {
        return $this->accountApi;
    }

    public function report(): TikTokReportApi
    {
        return $this->reportApi;
    }
}

