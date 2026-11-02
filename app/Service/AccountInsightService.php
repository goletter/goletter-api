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

namespace App\Service;

use App\Amqp\Queue\AccountInsightQueue;
use App\Service\Adv\AdvResolver;
use Carbon\Carbon;
use Goletter\Server\BaseService;
use Hyperf\Collection\Arr;
use Hyperf\Di\Annotation\Inject;

class AccountInsightService extends BaseService
{
    #[Inject]
    private AdvResolver $advResolver;

    public function paginate(array $params)
    {
        $platform = Arr::get($params, 'platform', 0);
        $token = Arr::get($params, 'token');
        $code = Arr::get($params, 'code');
        $startAt = Arr::get($params, 'start_at');
        $endAt = Arr::get($params, 'end_at');

        $startAt = Carbon::now()->subDays(7)->toDateString();
        $endAt = Carbon::now()->toDateString();

        $adv = $this->advResolver->resolveAdv($platform, $token);
        return $adv->report()->iterateDailyReport([
            'code' => $code,
            'start_at' => $startAt,
            'end_at' => $endAt,
        ]);
    }

    public function create(array $data)
    {
        $token = Arr::get($data, 'token');
        $code = Arr::get($data, 'code');
        $tenantId = Arr::get($data, 'tenant_id', 1);
        $platform = Arr::get($data, 'platform', 0);
        $startAt = Arr::get($data, 'start_at');
        $endAt = Arr::get($data, 'end_at');

        $payload = [
            'tenant_id' => $tenantId,
            'code' => $code,
            'access_token' => $token,
            'platform' => $platform,
        ];
        if ($startAt) {
            $payload['start_at'] = $startAt;
        }
        if ($endAt) {
            $payload['end_at'] = $endAt;
        }

        // serialKey = tenant_id + code，不同账户进不同分片并行
        AccountInsightQueue::produce($payload);
    }
}
