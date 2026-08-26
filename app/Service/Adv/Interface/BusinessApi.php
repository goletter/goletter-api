<?php

namespace App\Service\Adv\Interface;

interface BusinessApi
{
    
    public function iterateSystemUsers(string $businessId, array $fields = ['id', 'name', 'role']):array;

    public function inviteBusinessUser(string $businessId, string $email, string $access_token, string $role = 'EMPLOYEE'):array;

    public function iterateBusinessUsers(
        string $businessId,
        array $fields = ['id', 'name', 'email', 'role']
    ): \Generator;

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
     * GET /{pixel_id}/shared_accounts?business={business_id}&fields=id,name,account_status
     */
    public function getPixelSharedAccounts(
        string $pixelId,
        string $businessId,
        array $fields = ['id', 'name', 'account_status']
    ): array;

    /**
     * BM 下客户端像素列表
     * GET /{business_id}/client_pixels?fields=id,name
     */
    public function getClientPixels(string $businessId, array $fields = ['id', 'name']): array;

    /**
     * BM 下自有像素列表
     * GET /{business_id}/owned_pixels?fields=id,name
     */
    public function getOwnedPixels(string $businessId, array $fields = ['id', 'name']): array;

    /**
     * 按个号ID查询用户信息（含真实邮箱）
     * GET /{user_id}?fields=name,email
     */
    public function getBusinessUser(string $userId, array $fields = ['id', 'name', 'email']): array;
}