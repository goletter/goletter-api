<?php
namespace App\Controller\Api\V3;

use App\Controller\AbstractController;

class UserController extends AbstractController
{
    public function index()
    {
        return ['version' => 'v3', 'data' => ['User A', 'User B']];
    }
}
