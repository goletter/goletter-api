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

namespace App\Model\Filters;

use Goletter\ModelFilter\ModelFilter;

class AccountDemandFilter extends ModelFilter
{
    public function orderNo($orderNo)
    {
        $this->where('order_no', 'like', "%{$orderNo}%");
    }
}
