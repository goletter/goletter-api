<?php

namespace App\Service\Adv\Facebook;

use App\Constants\LogTypeConstant;
use App\Service\Adv\AdvException;
use App\Service\Adv\Interface\BusinessApi;
use Goletter\Adv\Platforms\Facebook\FacebookBusiness;
use Goletter\Adv\Platforms\Facebook\FacebookClient;

class FacebookBusinessApi extends AdvException implements BusinessApi
{
    protected FacebookBusiness $facebookBusiness;

    public function __construct(FacebookClient $client)
    {
        $this->facebookBusiness = new FacebookBusiness($client);
    }

    /**
     * 获取账户
     * @param string $businessId
     * @param array $fields
     * @return array
     */
    public function listAdAccounts(string $businessId, array $fields = ['id', 'name', 'account_status', 'spend_cap', 'amount_spent', 'currency', 'created_time', 'timezone_offset_hours_utc', 'business_country_code', 'business', 'funding_source_details']): array
    {
        try {
            return $this->facebookBusiness->listAdAccounts($businessId, $fields);
        } catch (\Exception $e) {
            throw new AdvException(500, $e->getMessage(), $e, 0, $businessId,'facebook', LogTypeConstant::Account);
        }
    }

    public function iterateSystemUsers(string $businessId, array $fields = ['id', 'name', 'role']): array
    {
        try {
            return $this->facebookBusiness->iterateSystemUsers($businessId, $fields);
        } catch (\Exception $e) {
            throw new AdvException(500, $e->getMessage(), $e, 0, $businessId,'facebook', LogTypeConstant::Account);
        }
    }

    /**
     * 邀请 BusinessUser
     * @param string $businessId
     * @param string $email
     * @param string $access_token
     * @param string $role
     * @return array
     */
    public function inviteBusinessUser(string $businessId, string $email, string $access_token, string $role = 'EMPLOYEE'): array
    {
        try {
            return $this->facebookBusiness->inviteBusinessUser($businessId, $email, $access_token, $role);
        } catch (\Exception $e) {
            throw new AdvException(500, $e->getMessage(), $e, 0, $businessId, 'facebook', LogTypeConstant::Account);
        }
    }

    /**
      * @desc 获取facebook的邮箱
      * @param string $businessId business
      * @return []
      * @author zhouzhou 2026-06-26
      */
    public function iterateBusinessUsers(
        string $businessId,
        array $fields = ['id', 'name', 'email', 'role']
    ): \Generator {
        try {
            return $this->facebookBusiness->iterateBusinessUsers($businessId, $fields);
        } catch (\Exception $e) {
            throw new AdvException(422, $e->getMessage(), $e, 0, $businessId, 'facebook', LogTypeConstant::Account);
        }
    }


    /**
     * @desc 获取bm下的邮箱
     * @param string $businessId
     * @param int $limit
     * @param string $after
     * @param array $fields
     * @return array []
     * @author zhouzhou 2026-08-19
     */
    public function getBusinessUsers(
        string $businessId,
        int $limit = 15,
        string $after = '',
        array $fields = ['id', 'email']
    ): array {
        try {
            return $this->facebookBusiness->getBusinessUsers($businessId, $limit, $after, $fields);
        } catch (\Exception $e) {
            throw new AdvException(500, $e->getMessage(), $e, 0, $businessId, 'facebook', LogTypeConstant::Account);
        }
    }


    public function getPendingUsers(string $businessId, int $limit = 1500): array
    {
        try {
            return $this->facebookBusiness->getPendingUsers($businessId, $limit);
        } catch (\Exception $e) {
            throw new AdvException(500, $e->getMessage(), $e, 0, $businessId, 'facebook', LogTypeConstant::Account);
        }
    }

    public function iteratePendingUsers(string $businessId, array $fields = ['created_time', 'id', 'email', 'role', 'status'], int $limit = 1500): array
    {
        try {
            return $this->facebookBusiness->iteratePendingUsers($businessId, $fields, $limit);
        } catch (\Exception $e) {
            throw new AdvException(500, $e->getMessage(), $e, 0, $businessId, 'facebook', LogTypeConstant::Account);
        }
    }

    public function deletePendingUser(string $pendingUserId): array
    {
        try {
            return $this->facebookBusiness->deletePendingUser($pendingUserId);
        } catch (\Exception $e) {
            throw new AdvException(500, $e->getMessage(), $e, 0, 0, 'facebook', LogTypeConstant::Account);
        }
    }

    public function deleteBusinessUser(string $businessUserId): array
    {
        try {
            return $this->facebookBusiness->deleteBusinessUser($businessUserId);
        } catch (\Exception $e) {
            throw new AdvException(500, $e->getMessage(), $e, 0, $businessUserId, 'facebook', LogTypeConstant::Account);
        }
    }
    
    public function getMe(): array
    {
        try {
            return $this->facebookBusiness->getMe();
        } catch (\Exception $e) {
            throw new AdvException(500, $e->getMessage(), $e, 0, 0, 'facebook', LogTypeConstant::Account);
        }
    }

    /**
     * 查询待审批的像素
     */
    public function getPendingSharedPixels(
        string $businessId,
        array $fields = ['id', 'primary_container_id', 'business', 'name', 'is_unavailable', 'agreement']
    ): array {
        try {
            return $this->facebookBusiness->getPendingSharedPixels($businessId, $fields);
        } catch (\Exception $e) {
            throw new AdvException(500, $e->getMessage(), $e, 0, $businessId, 'facebook', LogTypeConstant::Account);
        }
    }

    /**
     * 接收像素：审批资产共享协议
     */
    public function approveAssetSharingAgreement(string $agreementId): array
    {
        try {
            return $this->facebookBusiness->approveAssetSharingAgreement($agreementId);
        } catch (\Exception $e) {
            throw new AdvException(500, $e->getMessage(), $e, 0, $agreementId, 'facebook', LogTypeConstant::Account);
        }
    }

    /**
     * 像素共享到广告账户
     */
    public function sharePixelToAdAccount(string $pixelId, string $businessId, string $accountId): array
    {
        try {
            return $this->facebookBusiness->sharePixelToAdAccount($pixelId, $businessId, $accountId);
        } catch (\Exception $e) {
            throw new AdvException(500, $e->getMessage(), $e, 0, $pixelId, 'facebook', LogTypeConstant::Account);
        }
    }

    /**
     * 像素已共享的广告账户列表
     */
    public function getPixelSharedAccounts(
        string $pixelId,
        string $businessId,
        array $fields = ['id', 'name', 'account_status']
    ): array {
        try {
            return $this->facebookBusiness->getPixelSharedAccounts($pixelId, $businessId, $fields);
        } catch (\Exception $e) {
            throw new AdvException(500, $e->getMessage(), $e, 0, $pixelId, 'facebook', LogTypeConstant::Account);
        }
    }

    /**
     * BM 下客户端像素列表
     */
    public function getClientPixels(string $businessId, array $fields = ['id', 'name']): array
    {
        try {
            return $this->facebookBusiness->getClientPixels($businessId, $fields);
        } catch (\Exception $e) {
            throw new AdvException(500, $e->getMessage(), $e, 0, $businessId, 'facebook', LogTypeConstant::Account);
        }
    }

    /**
     * BM 下自有像素列表
     */
    public function getOwnedPixels(string $businessId, array $fields = ['id', 'name']): array
    {
        try {
            return $this->facebookBusiness->getOwnedPixels($businessId, $fields);
        } catch (\Exception $e) {
            throw new AdvException(500, $e->getMessage(), $e, 0, $businessId, 'facebook', LogTypeConstant::Account);
        }
    }

    /**
     * 按个号ID查询用户信息（含真实邮箱）
     */
    public function getBusinessUser(string $userId, array $fields = ['id', 'name', 'email']): array
    {
        try {
            return $this->facebookBusiness->getBusinessUser($userId, $fields);
        } catch (\Exception $e) {
            throw new AdvException(500, $e->getMessage(), $e, 0, $userId, 'facebook', LogTypeConstant::Account);
        }
    }
}