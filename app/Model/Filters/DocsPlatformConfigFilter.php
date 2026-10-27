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

class DocsPlatformConfigFilter extends ModelFilter
{
    public function name($name)
    {
        $this->where('name', 'like', "$name%");
    }
}
