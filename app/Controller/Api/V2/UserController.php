<?php
namespace App\Controller\Api\V2;

use App\Controller\AbstractController;

class UserController extends AbstractController
{
    public function index()
    {
        return ['version' => 'v2', 'data' => ['User A', 'User B']];
    }
}
