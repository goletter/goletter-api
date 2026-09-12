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

namespace App\Command;

use Goletter\Server\Service\QueueService;
use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Di\Annotation\Inject;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

#[Command]
class TestCommand extends HyperfCommand
{
    #[Inject]
    private QueueService $queueService;

    public function __construct()
    {
        parent::__construct('test:to');
    }

    public function configure(): void
    {
        parent::configure();
        $this->setDescription('测试');
    }

    public function handle()
    {
        $bmId = '719354937788295';
        $token = 'EAAGNO4a7r2wBSWi9Ez4CMZCdB1faAEtcHidGn34d9IpDKKa7GtN3Ek1LhsTJ1YVb5t8yQYFjEIziRfHZAPL0IiU9ZBDq5xmFWllgHyJpyTMZBpA5LRb2N9gYANZAPpQ62eZBZBhiPlaY3pTBGfV7J67DstFe4PXpXauaWvZBluUdZAGq5MN877FR80XEk3FjZBDgZDZD';

        $http = new Client([
            'timeout' => 30,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            ]
        ]);

        try {
            // 方案一：尝试获取节点（去掉自定义 header）
            $response = $http->get("https://graph.facebook.com/{$bmId}", [
                'query' => [
                    'access_token' => $token,
                ]
            ]);

            $result = json_decode($response->getBody()->getContents(), true);

            // 如果成功拿到 200 返回，说明存活
            dd([
                'status' => 'Live',
                'data' => $result
            ]);

        } catch (RequestException $exception) {
            // 如果抛出 400 异常，解析响应体中的具体错误码
            if ($exception->hasResponse()) {
                $errorBody = json_decode($exception->getResponse()->getBody()->getContents(), true);
                $code = $errorBody['error']['code'] ?? null;
                $message = $errorBody['error']['message'] ?? '';

                // 核心判断逻辑
                if ($code === 1 || $code === 200) {
                    // Code 1 (Invalid request) / Code 200 (Permission error):
                    // 说明 BM ID 是真实存在的，只是 Meta 限制了未授权账号读取详细属性
                    dd(['status' => 'Live (受限/存在)', 'code' => $code, 'raw' => $errorBody]);
                } elseif ($code === 100 || $code === 33) {
                    // Code 100 (Object does not exist) / Code 33:
                    // 说明该 ID 不存在、已被彻底注销或永久封禁删号
                    dd(['status' => 'Dead (不存在或已封)', 'code' => $code, 'raw' => $errorBody]);
                } else {
                    dd(['status' => 'Unknown Error', 'code' => $code, 'message' => $message]);
                }
            }

            dd('Network Error: ' . $exception->getMessage());
        }
    }
}
