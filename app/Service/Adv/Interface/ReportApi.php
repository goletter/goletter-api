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

namespace App\Service\Adv\Interface;

interface ReportApi
{
    /**
     * 获取消耗.
     */
    public function iterateDailyReport(array $params): array;

    /**
     * 分页拉取 Insights.
     */
    public function paginateInsights(array $params): array;
}
