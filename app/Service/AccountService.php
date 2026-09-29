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

use Goletter\Server\BaseService;
use function Goletter\Utils\di;

class AccountService extends BaseService
{
    public function create($data)
    {
        return di()->get(\App\Service\Action\Account\CreateAction::class)->handle($data);
    }

    public function update($data)
    {
        return di()->get(\App\Service\Action\Account\UpdateAction::class)->handle($data);
    }
}
