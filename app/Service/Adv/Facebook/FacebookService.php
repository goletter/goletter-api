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

    protected int|string $busineId;

    protected int|string $platformId;

    public function __construct(
        string $token,
        int|string $busineId = 0,
        int|string $platformId = 0,
        string $apiVersion = 'v24.0',
    ) {
        // busineId/platformId 仅业务侧使用，不可传入 FacebookClient（第 2 参是 apiVersion）
        $this->busineId = $busineId;
        $this->platformId = $platformId;

        $client = new FacebookClient($token, $apiVersion);
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
