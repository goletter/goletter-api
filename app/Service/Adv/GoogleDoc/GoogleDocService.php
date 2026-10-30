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

namespace App\Service\Adv\GoogleDoc;

use Goletter\Docs\DocsManager;

use function Goletter\Utils\di;

/**
 * Google 文档（Sheets）开户渠道聚合服务
 *
 * 负责将 channel token 交给账户 API，对外提供与其它 Adv 平台一致的入口。
 * 注意：通过 new 创建，不要 #[Inject] DocsManager，避免 Job 序列化问题。
 */
class GoogleDocService
{
    protected GoogleDocAccountApi $accountApi;

    public function __construct(string $token)
    {
        /** @var DocsManager $docs */
        $docs = di()->get(DocsManager::class);
        $this->accountApi = new GoogleDocAccountApi($token, $docs);
    }

    /**
     * 账户相关能力.
     */
    public function account(): GoogleDocAccountApi
    {
        return $this->accountApi;
    }
}
