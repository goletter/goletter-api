<?php

declare(strict_types=1);

namespace App\Controller\Admin\V1;

use App\Controller\AbstractController;
use Hyperf\HttpServer\Contract\RequestInterface;

class UserController extends AbstractController
{
    public function index(RequestInterface $request)
    {
        return ['version' => 'v1', 'scope' => 'admin', 'data' => ['Admin A', 'Admin B']];
    }
}
