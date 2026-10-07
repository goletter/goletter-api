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

namespace App\Controller;

use App\Model\Account;

class IndexController extends AbstractController
{
    public function index()
    {
        $accounts = Account::query()->get();

        return $this->collection($accounts);
    }
}
