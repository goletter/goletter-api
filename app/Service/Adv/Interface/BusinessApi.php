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

namespace App\Service\Adv\Interface;

use Generator;

interface BusinessApi
{
    public function iterateSystemUsers(string $businessId, array $fields = ['id', 'name', 'role']): array;

    public function inviteBusinessUser(string $businessId, string $email, string $access_token, string $role = 'EMPLOYEE'): array;

    public function iterateBusinessUsers(
        string $businessId,
        array $fields = ['id', 'name', 'email', 'role']
    ): Generator;

    public function getBusinessUsers(
        string $businessId,
        int $limit = 15,
        string $after = '',
        array $fields = ['id', 'email']
    ): array;

    public function getPendingUsers(string $businessId, int $limit = 1500): array;

    public function iteratePendingUsers(string $businessId, array $fields = ['created_time', 'id', 'email', 'role', 'status'], int $limit = 1500): array;

    public function deletePendingUser(string $pendingUserId): array;

    public function deleteBusinessUser(string $businessUserId): array;

    public function getMe(): array;

    public function getPendingSharedPixels(
        string $businessId,
        array $fields = ['id', 'primary_container_id', 'business', 'name', 'is_unavailable', 'agreement']
    ): array;

    public function approveAssetSharingAgreement(string $agreementId): array;

    public function sharePixelToAdAccount(string $pixelId, string $businessId, string $accountId): array;

    /**
     * 像素已共享的广告账户列表
     * GET /{pixel_id}/shared_accounts?business={business_id}&fields=id,name,account_status.
     */
    public function getPixelSharedAccounts(
        string $pixelId,
        string $businessId,
        array $fields = ['id', 'name', 'account_status']
    ): array;

    /**
     * BM 下客户端像素列表
     * GET /{business_id}/client_pixels?fields=id,name.
     */
    public function getClientPixels(string $businessId, array $fields = ['id', 'name']): array;

    /**
     * BM 下自有像素列表
     * GET /{business_id}/owned_pixels?fields=id,name.
     */
    public function getOwnedPixels(string $businessId, array $fields = ['id', 'name']): array;

    /**
     * 按个号ID查询用户信息（含真实邮箱）
     * GET /{user_id}?fields=name,email.
     */
    public function getBusinessUser(string $userId, array $fields = ['id', 'name', 'email']): array;

    /**
     * 查看目录在 BM 的权限级别
     * GET /{catalog_id}/agencies.
     */
    public function getCatalogAgencies(
        string $catalogId,
        array $fields = ['business', 'permitted_tasks', 'permitted_roles']
    ): array;

    /**
     * 查询单个目录名称 / 行业
     * GET /{catalog_id}?fields=id,name,vertical.
     */
    public function getProductCatalog(
        string $catalogId,
        array $fields = ['id', 'name', 'vertical']
    ): array;

    /**
     * 查询 BM 被共享过来的目录
     * GET /{business_id}/client_product_catalogs.
     */
    public function getClientProductCatalogs(
        string $businessId,
        array $fields = ['id', 'name', 'business', 'vertical'],
        int $limit = 1500
    ): array;

    /**
     * 给人员分配 catalog 权限
     * POST /{catalog_id}/assigned_users.
     */
    public function assignCatalogUser(string $catalogId, string $userId, array $tasks = ['MANAGE']): array;

    /**
     * GET /{page_id}/assigned_users?business={business_id}.
     */
    public function listPageAssignedUsers(string $pageId, string $businessId): array;

    /**
     * POST /{page_id}/assigned_users
     * tasks: MANAGE, CREATE_CONTENT, MODERATE, ADVERTISE, ANALYZE.
     */
    public function assignPageUser(string $pageId, string $userId, string $businessId, array $tasks = ['ADVERTISE']): array;

    /**
     * DELETE /{page_id}/assigned_users.
     */
    public function removePageAssignedUser(string $pageId, string $userId, string $businessId): array;

    /**
     * 验证用户已被授权的目录
     * GET /{user_id}/assigned_product_catalogs.
     */
    public function getAssignedProductCatalogs(
        string $userId,
        array $fields = ['id', 'name']
    ): array;
}
