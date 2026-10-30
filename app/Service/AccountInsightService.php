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
use Goletter\Server\BaseService;
use Hyperf\Collection\Arr;
use Hyperf\Di\Annotation\Inject;

class AccountInsightService extends BaseService
{
    #[Inject]
    private AdvResolver $advResolver;

    public function index()
    {
        //
    }
}
