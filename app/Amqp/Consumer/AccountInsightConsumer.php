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
use App\Amqp\Sharded\ShardedConsumer;
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

        $startAt = Carbon::now()->subDays(7)->toDateString();
        $endAt = Carbon::now()->toDateString();

        $adv = $this->advResolver->resolveAdv(0, $token);
        $accountInsights = $adv->report()->iterateDailyReport([
            'code' => $code,
            'start_at' => $startAt,
            'end_at' => $endAt,
        ]);

        $newAccountInsights = [];
        foreach ($accountInsights as $accountInsight) {
            $newAccountInsights[] = [
                'tenant_id' => $tenantId,
                'code' => $code,
                'spend' => Arr::get($accountInsight, 'spend', 0),
                'start_at' => Arr::get($accountInsight, 'date_start'),
                'end_at' => Arr::get($accountInsight, 'date_stop'),
                'created_at' => Carbon::now()->toDateTimeString(),
                'updated_at' => Carbon::now()->toDateTimeString(),
            ];
        }

        if (count($newAccountInsights) > 0) {
            AccountInsight::batchUpdateOrCreate(
                'account_insights',
                $newAccountInsights,
                ['code', 'start_at', 'end_at'], // 唯一键
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
