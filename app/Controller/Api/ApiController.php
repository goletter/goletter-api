<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Controller\BaseDispatcherController;
use Hyperf\HttpServer\Contract\RequestInterface;

/**
 * API 统一网关 → App\Controller\Api\V{n}\{Resource}Controller
 */
class ApiController extends BaseDispatcherController
{
    protected string $controllerNamespace = 'App\\Controller\\Api';

    public function handle(RequestInterface $request, string $resource, $seg1 = null, $seg2 = null)
    {
        return $this->handleResource($request, $resource, $seg1, $seg2);
    }
}