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
        $method = $this->request->getMethod();
        $traceId = $this->request->input('trace_id');
        $data = $this->request->all();

        return $this->success([
            'method' => $method,
            'trace_id' => $traceId,
            ...$data,
        ]);
    }
}
