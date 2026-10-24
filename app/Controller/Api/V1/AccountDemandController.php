<?php
namespace App\Controller\Api\V1;

use App\Controller\AbstractController;
use Hyperf\HttpServer\Contract\RequestInterface;

class AccountDemandController extends AbstractController
{
    public function index()
    {
        return ['version' => 'v1', 'data' => ['User A account_demands', 'User B']];
    }

    public function export(RequestInterface $request)
    {
        return ['version' => 'v1', 'exported' => true, 'filters' => $request->all()];
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
