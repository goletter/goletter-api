<?php

declare(strict_types=1);

namespace App\Logic\Account;

use App\Service\Adv\AdvResolver;
use Goletter\Server\BaseService;
use Hyperf\Collection\Arr;
use Hyperf\Di\Annotation\Inject;

/**
  * @desc 详情
  * @author yong 2026-11-09
  */
class ShowLogic extends BaseService
{
    #[Inject]
    private AdvResolver $advResolver;

    public function handle(array $data)
    {
        $token = Arr::get($data, 'token');
        $code = Arr::get($data, 'code');
        $adv = $this->advResolver->resolveAdv(0, $token);

        return $adv->account()->getAccount($code);
    }
}
