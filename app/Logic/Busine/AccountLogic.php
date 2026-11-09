<?php

declare(strict_types=1);

namespace App\Logic\Busine;

use App\Service\Adv\AdvResolver;
use Goletter\Observability\Log\Log;
use Goletter\Server\BaseService;
use Hyperf\Collection\Arr;
use Hyperf\Di\Annotation\Inject;

/**
  * @desc Bm下账户列表
  * @author yong 2026-11-09
  */
class AccountLogic extends BaseService
{
    #[Inject]
    private AdvResolver $advResolver;

    public function handle(array $data)
    {
        $token = Arr::get($data, 'token');
        $businesId = Arr::get($data, 'busine_code');
        $fields = [
            'id',
            'name',
            'account_status',
            'timezone_offset_hours_utc',
            'business',
            'owner',
        ];
        $adv = $this->advResolver->resolveAdv(0, $token);
        $accounts = $adv->business()->listAdAccounts($businesId, $fields);

        return $accounts;
    }
}
