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

namespace App\Amqp\Consumer;

use App\Amqp\Queue\AccountInsightQueue;
use Goletter\Amqp\Sharded\ShardedConsumer;
use App\Model\AccountInsight;
use App\Service\Adv\AdvResolver;
use Carbon\Carbon;
use Hyperf\Amqp\Result;
use Hyperf\Collection\Arr;
use Hyperf\Di\Annotation\Inject;
use PhpAmqpLib\Message\AMQPMessage;

class AccountInsightConsumer extends ShardedConsumer
{
    #[Inject]
    private AdvResolver $advResolver;

    protected function handle(mixed $data, AMQPMessage $message): Result
    {
        $tenantId = Arr::get($data, 'tenant_id');
        $token = Arr::get($data, 'access_token');
        $code = Arr::get($data, 'code');
        $platform = Arr::get($data, 'platform', 0);

        $startAt = Arr::get($data, 'start_at') ?: Carbon::now()->subDays(3)->toDateString();
        $endAt = Arr::get($data, 'end_at') ?: Carbon::now()->toDateString();

        $adv = $this->advResolver->resolveAdv($platform, $token);
        $accountInsights = $adv->report()->iterateDailyReport([
            'code' => $code,
            'start_at' => $startAt,
            'end_at' => $endAt,
        ]);

        $newAccountInsights = [];
        foreach ($accountInsights as $item) {
            $spend = Arr::get($item, 'spend', 0);
            if ($spend > 0) {
                $newAccountInsights[] = [
                    'tenant_id' => $tenantId,
                    'code' => $code,
                    'spend' => Arr::get($item, 'spend', 0),
                    'start_at' => Arr::get($item, 'date_start'),
                    'end_at' => Arr::get($item, 'date_stop'),
                    'created_at' => Carbon::now()->toDateTimeString(),
                    'updated_at' => Carbon::now()->toDateTimeString(),
                ];
            }
        }

        if (count($newAccountInsights) > 0) {
            AccountInsight::batchUpdateOrCreate(
                'account_insights',
                $newAccountInsights,
                ['tenant_id', 'code', 'start_at', 'end_at'], // 唯一键
                ['spend', 'updated_at']  // 重复更新字段
            );
        }

        return Result::ACK;
    }

    protected static function queueClass(): string
    {
        return AccountInsightQueue::class;
    }
}
