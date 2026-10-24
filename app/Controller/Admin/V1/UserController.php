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

    public function create(RequestInterface $request)
    {
        return ['version' => 'v1', 'scope' => 'admin', 'created' => true, 'payload' => $request->all()];
    }

    public function update(RequestInterface $request, $id)
    {
        return ['version' => 'v1', 'scope' => 'admin', 'updated' => true, 'id' => $id];
    }

    public function delete(RequestInterface $request, $id)
    {
        return ['version' => 'v1', 'scope' => 'admin', 'deleted' => true, 'id' => $id];
    }
}
