<?php

declare(strict_types=1);

namespace App\Amqp\Consumer;

use App\Amqp\Queue\AccountAdvReportQueue;
use App\Model\AccountAdvReport;
use App\Service\Adv\AdvResolver;
use Carbon\Carbon;
use Goletter\Amqp\Sharded\ShardedConsumer;
use Hyperf\Amqp\Result;
use Hyperf\Collection\Arr;
use Hyperf\Di\Annotation\Inject;
use PhpAmqpLib\Message\AMQPMessage;

class AccountAdvReportConsumer extends ShardedConsumer
{
    #[Inject]
    private AdvResolver $advResolver;

    protected function handle(mixed $data, AMQPMessage $message): Result
    {
        $tenantId = Arr::get($data, 'tenant_id');
        $token = Arr::get($data, 'access_token');
        $code = Arr::get($data, 'code');
        $busineCode = Arr::get($data, 'busine_id');
        $platform = Arr::get($data, 'platform', 0);

        $startAt = Arr::get($data, 'start_at') ?: Carbon::now()->subDays(3)->toDateString();
        $endAt = Arr::get($data, 'end_at') ?: Carbon::now()->toDateString();

        $adv = $this->advResolver->resolveAdv($platform, $token);
        $accountInsights = $adv->report()->insightDailyReport([
            'code' => $code,
            'start_at' => $startAt,
            'end_at' => $endAt,
        ]);

        $newReports = [];
        foreach ($accountInsights as $item) {
            $purchase = $this->extractActionValue(
                (array) ($item['actions'] ?? []),
                ['purchase', 'omni_purchase', 'offsite_conversion.fb_pixel_purchase']
            );
            $costPerPurchase = $this->extractActionValue(
                (array) ($item['cost_per_action_type'] ?? []),
                ['purchase', 'omni_purchase', 'offsite_conversion.fb_pixel_purchase']
            );
            $reg = $this->extractActionValue(
                (array) ($item['actions'] ?? []),
                ['complete_registration', 'omni_complete_registration']
            );
            $costPerReg = $this->extractActionValue(
                (array) ($item['cost_per_action_type'] ?? []),
                ['complete_registration', 'omni_complete_registration']
            );
            $roas = $this->extractActionValue(
                (array) ($item['action_values'] ?? []),
                ['purchase_roas', 'omni_purchase_roas']
            );

            $newReports[] = [
                'tenant_id' => $tenantId,
                'busine_code' => $busineCode,
                'code' => Arr::get($item, 'account_id'),
                'spend' => (string) ($item['spend'] ?? '0'),
                'dollar' => (string) ($item['spend'] ?? '0'),
                'campaign_name' => (string) ($item['campaign_name'] ?? ''),
                'campaign_id' => (string) ($item['campaign_id'] ?? ''),
                'reach' => (string) ($item['reach'] ?? ''),
                'impressions' => (string) ($item['impressions'] ?? ''),
                'frequency' => (string) ($item['frequency'] ?? ''),
                'clicks' => (string) ($item['clicks'] ?? ''),
                'cpc' => (string) ($item['cpc'] ?? ''),
                'purchase' => (string) ($purchase ?? ''),
                'cost_per_purchase' => (string) ($costPerPurchase ?? ''),
                'roas' => (string) ($roas ?? ''),
                'reg' => (string) ($reg ?? ''),
                'cost_per_reg' => (string) ($costPerReg ?? ''),
                'start_at' => $item['date_start'] ?? null,
                'end_at' => $item['date_stop'] ?? null,
                'created_at' => Carbon::now()->toDateTimeString(),
                'updated_at' => Carbon::now()->toDateTimeString(),
            ];
        }

        if (count($newReports) > 0) {
            AccountAdvReport::batchUpdateOrCreate(
                'account_adv_reports',
                $newReports,
                ['tenant_id', 'code', 'campaign_id', 'start_at'], // 唯一键
                ['spend', 'dollar', 'reach', 'impressions', 'frequency', 'clicks', 'cpc', 'purchase', 'cost_per_purchase', 'roas', 'cost_per_reg', 'updated_at']  // 重复更新字段
            );
        }

        return Result::ACK;
    }

    protected static function queueClass(): string
    {
        return AccountAdvReportQueue::class;
    }

    private function extractActionValue(array $rows, array $types): ?string
    {
        foreach ($rows as $row) {
            $type = (string) ($row['action_type'] ?? '');
            if (in_array($type, $types, true)) {
                return (string) ($row['value'] ?? '0');
            }
        }
        return null;
    }
}
