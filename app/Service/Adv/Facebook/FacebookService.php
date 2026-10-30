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

    protected FacebookAdApi $adApi;

    public function __construct(string $token, int $busineId = 0, int $platformId = 0)
    {
        $client = new FacebookClient($token, (string) $busineId, $platformId);
        $this->accountApi = new FacebookAccountApi($client);
        $this->reportApi = new FacebookReportApi($client);
        $this->businessApi = new FacebookBusinessApi($client);
        $this->adApi = new FacebookAdApi($client);
    }

    /**
     * 账户相关能力.
     */
    public function account(): FacebookAccountApi
    {
        return $this->accountApi;
    }

    /**
     * 报表相关能力.
     */
    public function report(): FacebookReportApi
    {
        return $this->reportApi;
    }

    /**
     * Bm.
     */
    public function business(): FacebookBusinessApi
    {
        return $this->businessApi;
    }

    /**
     * 广告投放（上传图片、创建系列/广告组/创意/广告）.
     */
    public function ad(): FacebookAdApi
    {
        return $this->adApi;
    }
}
