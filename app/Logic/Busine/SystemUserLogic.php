<?php

declare(strict_types=1);

namespace App\Logic\Busine;

use App\Service\Adv\AdvResolver;
use Goletter\Server\BaseService;
use Hyperf\Collection\Arr;
use Hyperf\Di\Annotation\Inject;

/**
  * @desc Bm下系统用户列表
  * @author yong 2026-11-09
  */
class SystemUserLogic extends BaseService
{
    #[Inject]
    private AdvResolver $advResolver;

    public function handle(array $data)
    {
        $token = Arr::get($data, 'token');
        $businesId = Arr::get($data, 'busine_code');

        $adv = $this->advResolver->resolveAdv(0, $token);
        $systemUsers = $adv->business()->iterateSystemUsers($businesId);

        return $systemUsers;
    }
}
