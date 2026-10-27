<?php
namespace App\Controller\Api\V1;

use App\Controller\AbstractController;

class DocsPlatformConfigController extends AbstractController
{
    public function index()
    {
        return $this->success();
    }
}
