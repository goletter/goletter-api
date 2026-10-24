<?php

declare(strict_types=1);

namespace App\Controller\Api\V1;

use App\Controller\AbstractController;
use Hyperf\HttpServer\Contract\RequestInterface;

class OrderController extends AbstractController
{
    public function index(RequestInterface $request)
    {
        return ['version' => 'v1', 'data' => [
            ['id' => 1, 'status' => 'paid'],
            ['id' => 2, 'status' => 'pending'],
        ]];
    }

    public function create(RequestInterface $request)
    {
        return ['version' => 'v1', 'created' => true, 'payload' => $request->all()];
    }

    public function update(RequestInterface $request, $id)
    {
        return ['version' => 'v1', 'updated' => true, 'id' => $id, 'payload' => $request->all()];
    }

    public function delete(RequestInterface $request, $id)
    {
        return ['version' => 'v1', 'deleted' => true, 'id' => $id];
    }
}
