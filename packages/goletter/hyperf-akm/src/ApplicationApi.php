<?php

declare(strict_types=1);

namespace Goletter\Akm;

/**
 * 账户管理 & 需求申请相关接口封装（/open_api/v1.0/application/...）
 */
class ApplicationApi
{
    public function __construct(
        protected AkmClient $client
    ) {
    }

    /* ====== 大媒体账户查询 ====== */

    /**
     * Facebook 账户列表
     * GET /open_api/v1.0/application/facebook/list
     */
    public function facebookAccounts(array $query = []): array
    {
        return $this->client->get('/open_api/v1.0/application/facebook/list', $query, 'GET:/application/facebook/list');
    }

    /**
     * Facebook 账户清零记录查询
     * POST /open_api/v1.0/application/facebook/get_account_clear_record
     */
    public function facebookAccountClearRecords(array $body): array
    {
        return $this->client->postJson(
            '/open_api/v1.0/application/facebook/get_account_clear_record',
            $body,
            [],
            'POST:/application/facebook/get_account_clear_record'
        );
    }

    /**
     * Google 账户列表
     * GET /open_api/v1.0/application/google/list
     */
    public function googleAccounts(array $query = []): array
    {
        return $this->client->get('/open_api/v1.0/application/google/list', $query, 'GET:/application/google/list');
    }

    /**
     * TikTok 账户列表
     * GET /open_api/v1.0/application/tiktok/list
     */
    public function tiktokAccounts(array $query = []): array
    {
        return $this->client->get('/open_api/v1.0/application/tiktok/list', $query, 'GET:/application/tiktok/list');
    }

    /**
     * 新兴媒体账户需求列表
     * GET /open_api/v1.0/application/small_medium/list
     */
    public function smallMediumList(array $query = []): array
    {
        return $this->client->get('/open_api/v1.0/application/small_medium/list', $query, 'GET:/application/small_medium/list');
    }

    /**
     * 新兴媒体账户需求详情
     * GET /open_api/v1.0/application/small_medium/list/{id}
     */
    public function smallMediumDetail(int $id): array
    {
        return $this->client->get("/open_api/v1.0/application/small_medium/list/{$id}", [], 'GET:/application/small_medium/list/{id}');
    }

    /**
     * 大媒体账户管理需求申请记录
     * GET /open_api/v1.0/application/operate_record
     */
    public function operateRecords(array $query = []): array
    {
        return $this->client->get('/open_api/v1.0/application/operate_record', $query, 'GET:/application/operate_record');
    }

    /* ====== Facebook 需求申请 ====== */

    /**
     * Facebook 账户额度调整申请
     * POST /open_api/v1.0/application/facebook/amount_spend
     */
    public function facebookAmountSpend(array $body): array
    {
        return $this->client->postJson('/open_api/v1.0/application/facebook/amount_spend', $body, [], 'POST:/application/facebook/amount_spend');
    }

    /**
     * Facebook 绑定/解绑 BM 申请
     * POST /open_api/v1.0/application/facebook/bind_bm
     */
    public function facebookBindBm(array $body): array
    {
        return $this->client->postJson('/open_api/v1.0/application/facebook/bind_bm', $body, [], 'POST:/application/facebook/bind_bm');
    }

    /**
     * Facebook 绑定/解绑 Pixel 申请
     * POST /open_api/v1.0/application/facebook/bind_pixel
     */
    public function facebookBindPixel(array $body): array
    {
        return $this->client->postJson('/open_api/v1.0/application/facebook/bind_pixel', $body, [], 'POST:/application/facebook/bind_pixel');
    }

    /**
     * Facebook 账户更名申请
     * POST /open_api/v1.0/application/facebook/rename
     */
    public function facebookRename(array $body): array
    {
        return $this->client->postJson('/open_api/v1.0/application/facebook/rename', $body, [], 'POST:/application/facebook/rename');
    }

    /* ====== Google 需求申请 ====== */

    /**
     * Google 账户额度变更申请
     * POST /open_api/v1.0/application/google/amount_spend
     */
    public function googleAmountSpend(array $body): array
    {
        return $this->client->postJson('/open_api/v1.0/application/google/amount_spend', $body, [], 'POST:/application/google/amount_spend');
    }

    /**
     * Google 授权个人邮箱（绑定/解绑）
     * POST /open_api/v1.0/application/google/user_role
     */
    public function googleUserRole(array $body): array
    {
        return $this->client->postJson('/open_api/v1.0/application/google/user_role', $body, [], 'POST:/application/google/user_role');
    }

    /**
     * Google 接受/拒绝/移除 MCC 邀请
     * POST /open_api/v1.0/application/google/link_mcc
     */
    public function googleLinkMcc(array $body): array
    {
        return $this->client->postJson('/open_api/v1.0/application/google/link_mcc', $body, [], 'POST:/application/google/link_mcc');
    }

    /**
     * Google 账户更名申请
     * POST /open_api/v1.0/application/google/rename
     */
    public function googleRename(array $body): array
    {
        return $this->client->postJson('/open_api/v1.0/application/google/rename', $body, [], 'POST:/application/google/rename');
    }

    /* ====== TikTok 需求申请 ====== */

    /**
     * TikTok 账户额度变更申请
     * POST /open_api/v1.0/application/tiktok/amount_spend
     */
    public function tiktokAmountSpend(array $body): array
    {
        return $this->client->postJson('/open_api/v1.0/application/tiktok/amount_spend', $body, [], 'POST:/application/tiktok/amount_spend');
    }

    /**
     * TikTok 绑定/解绑 BC_ID
     * POST /open_api/v1.0/application/tiktok/bind_bc
     */
    public function tiktokBindBc(array $body): array
    {
        return $this->client->postJson('/open_api/v1.0/application/tiktok/bind_bc', $body, [], 'POST:/application/tiktok/bind_bc');
    }

    /**
     * TikTok 绑定/解绑邮箱
     * POST /open_api/v1.0/application/tiktok/bind_email
     */
    public function tiktokBindEmail(array $body): array
    {
        return $this->client->postJson('/open_api/v1.0/application/tiktok/bind_email', $body, [], 'POST:/application/tiktok/bind_email');
    }

    /**
     * TikTok 账户更名申请
     * POST /open_api/v1.0/application/tiktok/rename
     */
    public function tiktokRename(array $body): array
    {
        return $this->client->postJson('/open_api/v1.0/application/tiktok/rename', $body, [], 'POST:/application/tiktok/rename');
    }

    /* ====== 新兴媒体（小媒体）需求申请 ====== */

    /**
     * 新兴媒体额度变更申请
     * POST /open_api/v1.0/application/small_medium/amount_spend
     */
    public function smallMediumAmountSpend(array $body): array
    {
        return $this->client->postJson('/open_api/v1.0/application/small_medium/amount_spend', $body, [], 'POST:/application/small_medium/amount_spend');
    }

    /**
     * 新兴媒体绑定申请
     * POST /open_api/v1.0/application/small_medium/bind
     */
    public function smallMediumBind(array $body): array
    {
        return $this->client->postJson('/open_api/v1.0/application/small_medium/bind', $body, [], 'POST:/application/small_medium/bind');
    }

    /**
     * 新兴媒体更名申请
     * POST /open_api/v1.0/application/small_medium/rename
     */
    public function smallMediumRename(array $body): array
    {
        return $this->client->postJson('/open_api/v1.0/application/small_medium/rename', $body, [], 'POST:/application/small_medium/rename');
    }

    /**
     * 新兴媒体其他申请
     * POST /open_api/v1.0/application/small_medium/other
     */
    public function smallMediumOther(array $body): array
    {
        return $this->client->postJson('/open_api/v1.0/application/small_medium/other', $body, [], 'POST:/application/small_medium/other');
    }
}

