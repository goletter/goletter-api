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

namespace App\Controller;

class IndexController extends AbstractController
{
    public function index()
    {
        $data = ['test' => 11];

        return $this->success($data);
    }

    public function test()
    {
        $data = $this->request->all();

        return $this->success($data);
    }
}
