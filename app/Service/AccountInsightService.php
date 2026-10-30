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
        $token = Arr::get($params, 'token');
        $code = Arr::get($params, 'code');
        $startAt = Arr::get($params, 'start_at');
        $endAt = Arr::get($params, 'end_at');

        $startAt = Carbon::now()->subDays(7)->toDateString();
        $endAt = Carbon::now()->toDateString();

        $adv = $this->advResolver->resolveAdv(0, $token);
        return $adv->report()->iterateDailyReport([
            'code' => $code,
            'start_at' => $startAt,
            'end_at' => $endAt,
        ]);
    }
}
