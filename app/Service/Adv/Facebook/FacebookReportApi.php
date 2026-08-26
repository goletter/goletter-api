<?php

namespace App\Service\Adv\Facebook;

use App\Service\Adv\AdvException;
use App\Service\Adv\Interface\ReportApi;
use Goletter\Adv\Platforms\Facebook\FacebookClient;
use Goletter\Adv\Platforms\Facebook\FacebookReport;

class FacebookReportApi extends AdvException implements ReportApi
{
    protected FacebookReport $facebookReport;

    public function __construct(FacebookClient $client)
    {
        $this->facebookReport = new FacebookReport($client);
    }

    /**
     * 获取消耗
     * @param array $params
     * @return array
     */
    public function iterateDailyReport(array $params): array
    {
        return [];
    }

    /**
     * 分页拉取 Insights（Cursor 方式）
     * @param array $params
     * @return array
     */
    public function paginateInsights(array $params): array
    {
        return [];
    }
}