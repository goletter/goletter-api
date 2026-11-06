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

namespace App\Service\Adv\TikTok;

use App\Service\Adv\AdvException;
use App\Service\Adv\Interface\ReportApi;
use Carbon\Carbon;
use Exception;
use Goletter\Adv\Platforms\TikTok\TikTokClient;
use Goletter\Adv\Platforms\TikTok\TikTokReport;
use Hyperf\Collection\Arr;

class TikTokReportApi extends AdvException implements ReportApi
{
    protected TikTokReport $tikTokReport;

    public function __construct(TikTokClient $client)
    {
        $this->tikTokReport = new TikTokReport($client);
    }

    /**
     * 获取消耗
     */
    public function iterateDailyReport(array $params): array
    {
        try {
            $items = [];
            $accountCode = Arr::get($params, 'code');
            $accountId = Arr::get($params, 'account_id', 0);
            $startAt = $params['start_at'] ?? Carbon::now()->subDays(3)->toDateString();
            $endAt = $params['end_at'] ?? Carbon::now()->toDateString();

            foreach ($this->tikTokReport->iterateReport(
                $accountCode,
                $startAt,
                $endAt
            ) as $item) {
                $spend = Arr::get($item, 'metrics.spend', 0);
                if ($spend > 0) {
                    $startAt = Carbon::parse(Arr::get($item, 'dimensions.stat_time_day'))->toDateString();
                    $items[] = [
                        'spend' => $spend,
                        'dollar' => $spend,
                        'start_at' => $startAt,
                        'end_at' => $startAt,
                    ];
                }
            }
            return $items;
        } catch (Exception $e) {
            throw new AdvException(500, $e->getMessage(), $e, $accountId, 0, 'tiktok', 'adv');
        }
    }

    /**
     * 获取账户报告（campaign 级，一次拉取）.
     */
    public function insightDailyReport(array $params): array
    {
        return [];
    }

    /**
     * 分页拉取 Insights（Cursor 方式）
     */
    public function paginateInsights(array $params): array
    {
        // TODO: Implement paginateInsights() method.
        return [];
    }
}
