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

namespace App\Logic\AccountDemand;

use App\Service\Adv\AdvResolver;
use Goletter\Server\BaseService;
use Hyperf\Collection\Arr;
use Hyperf\Di\Annotation\Inject;

/**
  * @desc 批量更新
  * @author yong 2026-11-07
  */
class BatchUpdateLogic extends BaseService
{
    #[Inject]
    private AdvResolver $advResolver;

    public function handle(array $data)
    {
        $token = Arr::get($data, 'token');
        $code = Arr::get($data, 'code');
        $adv = $this->advResolver->resolveAdv(0, $token);

        $adv->account()->getAccount('2222');
    }
}
