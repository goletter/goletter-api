<?php

declare(strict_types=1);

namespace App\Controller\FaceBook\V1;

use App\Callback\CallbackDispatcher;
use App\Controller\AbstractController;
use Hyperf\Di\Annotation\Inject;
use RuntimeException;

/**
 * @desc 第三方入站回调统一入口
 */
class CallbackController extends AbstractController
{
    #[Inject]
    protected CallbackDispatcher $dispatcher;

    /**
     * @desc 入站回调分发
     * POST /api/callback/handle
     * Body 示例: {"event":"ping","data":{"a":1}}
     */
    public function handle()
    {
        $body = $this->request->all();
        $event = $this->resolveEvent($body);
        if ($event === '') {
            $this->fail(400, '无法识别事件（请传 event，或按渠道字段解析）');
        }

        try {
            $result = $this->dispatcher->dispatch($event, $body);
        } catch (RuntimeException $e) {
            $this->fail(404, $e->getMessage());
        }

        return $this->success($result ?? ['ok' => true]);
    }

    /**
     * @param array<string, mixed> $body
     */
    private function resolveEvent(array $body): string
    {
        if (! empty($body['event']) && is_string($body['event'])) {
            return $body['event'];
        }

        // 渠道字段示例（按需打开）：
        // if (isset($body['message'])) {
        //     return 'message';
        // }
        // if (isset($body['callback_query'])) {
        //     return 'callback_query';
        // }

        return '';
    }
}
