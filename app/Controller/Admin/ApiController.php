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

namespace App\Controller\Admin;

use App\Controller\BaseDispatcherController;
use Hyperf\HttpServer\Contract\RequestInterface;

/**
 * Admin 统一网关（路由见 config/routes/admin.php + config/autoload/resources.php）.
 */
class ApiController extends BaseDispatcherController
{
    protected string $controllerNamespace = 'App\Controller\Admin';

    public function handle(RequestInterface $request, string $resource, $seg1 = null, $seg2 = null)
    {
        return $this->handleResource($request, $resource, $seg1, $seg2);
    }
}
