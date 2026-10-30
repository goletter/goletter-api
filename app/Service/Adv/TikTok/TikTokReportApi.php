<?php

namespace App\Service\Adv\TikTok;

use App\Service\Adv\AdvException;
use App\Service\Adv\Interface\ReportApi;
use Carbon\Carbon;
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
     * @param array $params
     * @return array
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
        } catch (\Exception $e) {
            throw new AdvException(500, $e->getMessage(), $e, $accountId, 0, 'tiktok', 'adv');
        }
    }

    /**
     * 分页拉取 Insights（Cursor 方式）
     * @param array $params
     * @return array
     */
    public function paginateInsights(array $params): array
    {
        // TODO: Implement paginateInsights() method.
        return [];
    }
}