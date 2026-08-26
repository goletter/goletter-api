<?php

namespace App\Service\Adv\Interface;

interface ReportApi
{
    /**
     * 获取消耗
     */
    public function iterateDailyReport(array $params):array;

    /**
     * 分页拉取 Insights
     */
    public function paginateInsights(array $params): array;
}