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

use App\Service\Adv\AdvException;
use Carbon\Carbon;
use Exception;
use Goletter\Adv\Platforms\Facebook\FacebookClient;
use Goletter\Adv\Platforms\Facebook\FacebookReport;
use Hyperf\Collection\Arr;

class FacebookReportApi extends AdvException
{
    protected FacebookReport $facebookReport;

    public function __construct(FacebookClient $client)
    {
        $this->facebookReport = new FacebookReport($client);
    }

    /**
     * 获取消耗（一次 time_range + time_increment=1，避免按天拆成多次 Graph）.
     * @return array
     */
    public function iterateDailyReport(array $params)
    {
        $accountId = Arr::get($params, 'code');
        $startAt = $params['start_at'] ?? Carbon::now()->subDays(3)->toDateString();
        $endAt = $params['end_at'] ?? Carbon::now()->toDateString();

        try {
            $fields = ['date_start', 'spend', 'impressions', 'clicks', 'date_stop'];
            return $this->facebookReport->getAllInsights(
                (string) $accountId,
                $startAt,
                $endAt,
                $fields
            );
        } catch (Exception $e) {
            throw new AdvException(500, $e->getMessage(), $e, $accountId, 0, 'facebook', 'adv');
        }
    }

    /**
     * 获取账户报告（campaign 级，一次拉取）.
     */
    public function insightDailyReport(array $params): array
    {
        $accountId = Arr::get($params, 'code');
        $startAt = $params['start_at'] ?? Carbon::now()->subDays(3)->toDateString();
        $endAt = $params['end_at'] ?? Carbon::now()->toDateString();

        try {
            $fields = [
                'account_name',
                'account_id',
                'campaign_id',
                'campaign_name',
                'date_start',
                'date_stop',
                'reach',
                'impressions',
                'frequency',
                'spend',
                'clicks',
                'cpc',
                'actions',
                'action_values',
                'cost_per_action_type',
            ];

            return $this->facebookReport->getAllInsights(
                (string) $accountId,
                $startAt,
                $endAt,
                $fields,
                'campaign',
                1,
                100
            );
        } catch (Exception $e) {
            throw new AdvException(500, $e->getMessage(), $e, $accountId, 0, 'facebook', 'adv');
        }
    }
}
