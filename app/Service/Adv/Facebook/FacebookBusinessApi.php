<?php

namespace App\Service\Adv\Facebook;

use App\Service\Adv\AdvException;
use Goletter\Adv\Platforms\Facebook\FacebookBusiness;
use Goletter\Adv\Platforms\Facebook\FacebookClient;

class FacebookBusinessApi extends AdvException
{
    protected FacebookBusiness $facebookBusiness;

    public function __construct(FacebookClient $client)
    {
        $this->facebookBusiness = new FacebookBusiness($client);
    }

    /**
     * 获取me(用于探针检测 total_time)
     * @return array
     */
    public function getMe(): array
    {
        try {
            $result = $this->facebookBusiness->getMe();
            return $result;
        } catch (\Exception $e) {
            throw new AdvException(500, $e->getMessage(), $e, 0, 0, 'facebook', 'adv');
        }
    }

    /**
     * 获取当前用户的所有 Business Manager
     * @param array $fields
     * @param int $limit
     * @return array
     */
    public function listBusinesses(array $fields = ['id', 'name', 'timezone_id', 'verification_status', 'permitted_roles'], int $limit = 100): array
    {
        try {
            $result = $this->facebookBusiness->listBusinesses($fields, $limit);

            return $result;
        } catch (\Exception $e) {
            throw new AdvException(500, $e->getMessage(), $e, 0, 0, 'facebook',  'adv');
        }
    }

    /**
     * 获取 Business Manager 下的业务用户列表
     * @param string $businessId
     * @param array $fields
     * @return array
     */
    public function listBusinessUsers(string $businessId, array $fields = ['id', 'name', 'email', 'role']): array
    {
        try {
            $result = $this->facebookBusiness->listBusinessUsers($businessId, $fields);

            return $result;
        } catch (\Exception $e) {
            throw new AdvException(500, $e->getMessage(), $e, 0, 0, 'facebook', 'adv');
        }
    }

    /**
     * 按个号ID查询用户信息（含真实邮箱）
     * @param string $userId
     * @param array $fields
     * @return array
     */
    public function getBusinessUser(string $userId, array $fields = ['id', 'name', 'email']): array
    {
        try {
            $result = $this->facebookBusiness->getBusinessUser($userId, $fields);

            return $result;
        } catch (\Exception $e) {
            throw new AdvException(500, $e->getMessage(), $e, 0, $userId, 'facebook', 'adv');
        }
    }

    /**
     * 获取账户
     * @param string $businessId
     * @param array $fields
     * @return array
     */
    public function listAdAccounts(string $businessId, array $fields = ['id','account_id', 'name', 'account_status', 'spend_cap', 'amount_spent', 'currency', 'created_time', 'timezone_offset_hours_utc', 'business_country_code', 'business', 'funding_source_details']): array
    {
        try {
            $result = $this->facebookBusiness->listAdAccounts($businessId, $fields,200);
            return $result;
        } catch (\Exception $e) {
            throw new AdvException(500, $e->getMessage(), $e, 0, $businessId,'facebook', 'adv');
        }
    }

    /**
     * 获取 BM 拥有的广告账户（owned_ad_accounts）
     * @param string $businessId
     * @param array $fields
     * @return array
     */
    public function listOwnedAdAccounts(string $businessId, array $fields = ['id','account_id', 'name', 'account_status', 'spend_cap', 'amount_spent', 'currency', 'created_time', 'timezone_offset_hours_utc', 'business_country_code', 'business', 'funding_source_details']): array
    {
        try {
            $result = $this->facebookBusiness->listOwnedAdAccounts($businessId, $fields,200);
            return $result;
        } catch (\Exception $e) {
            throw new AdvException(500, $e->getMessage(), $e, 0, $businessId, 'facebook', 'adv');
        }
    }

    /**
     * 获取Business Manager 下的系统用户
     * @param string $businessId
     * @param array $fields
     * @return array
     */
    public function iterateSystemUsers(string $businessId, array $fields = ['id', 'name', 'role']): array
    {
        try {
            $result = $this->facebookBusiness->iterateSystemUsers($businessId, $fields);
            return $result;
        } catch (\Exception $e) {
            throw new AdvException(500, $e->getMessage(), $e, 0, $businessId,'facebook', 'adv');
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
            $result = $this->facebookBusiness->inviteBusinessUser($businessId, $email, $access_token, $role);
            return $result;
        } catch (\Exception $e) {
            throw new AdvException(500, $e->getMessage(), $e, 0, $businessId, 'facebook', 'adv');
        }
    }

    /**
     * @desc 获取facebook的邮箱
     * @param string $businessId business
     * @return []
     */
    public function iterateBusinessUsers(
        string $businessId,
        array $fields = ['id', 'name', 'email', 'role']
    ): \Generator {
        try {
            $result = $this->facebookBusiness->iterateBusinessUsers($businessId, $fields);
            return $result;
        } catch (\Exception $e) {
            throw new AdvException(422, $e->getMessage(), $e, 0, $businessId, 'facebook', 'adv');
        }
    }

    /**
     * @desc 获取bm下的邮箱
     * @param string $businessId
     * @param int $limit
     * @param string $after
     * @param array $fields
     * @return array []
     */
    public function getBusinessUsers(
        string $businessId,
        int $limit = 15,
        string $after = '',
        array $fields = ['id', 'email']
    ): array {
        try {
            $result = $this->facebookBusiness->getBusinessUsers($businessId, $limit, $after, $fields);
            return $result;
        } catch (\Exception $e) {
            throw new AdvException(500, $e->getMessage(), $e, 0, $businessId, 'facebook', 'adv');
        }
    }

    /**
     * 获取邀请的邮箱数据
     * @param string $businessId
     * @param int $limit
     * @return array
     */
    public function getPendingUsers(string $businessId, int $limit = 1500): array
    {
        try {
            $result = $this->facebookBusiness->getPendingUsers($businessId, $limit);
            return $result;
        } catch (\Exception $e) {
            throw new AdvException(500, $e->getMessage(), $e, 0, $businessId, 'facebook', 'adv');
        }
    }

    /**
     * 获取邀请中的邮箱数据-分页拉(数据量大时用)
     * @param string $businessId
     * @param array $fields
     * @param int $limit
     * @return array
     */
    public function iteratePendingUsers(string $businessId, array $fields = ['created_time', 'id', 'email', 'role', 'status'], int $limit = 1500): array
    {
        try {
            $result = $this->facebookBusiness->iteratePendingUsers($businessId, $fields, $limit);
            return $result;
        } catch (\Exception $e) {
            throw new AdvException(500, $e->getMessage(), $e, 0, $businessId, 'facebook', 'adv');
        }
    }

    /**
     * 移除邀请中的邮箱
     * @param string $pendingUserId
     * @return array
     */
    public function deletePendingUser(string $pendingUserId): array
    {
        try {
            $result = $this->facebookBusiness->deletePendingUser($pendingUserId);
            return $result;
        } catch (\Exception $e) {
            throw new AdvException(500, $e->getMessage(), $e, 0, 0, 'facebook', 'adv');
        }
    }

    /**
     * 移除 BM 下已加入的个号
     * @param string $businessUserId
     * @return array
     */
    public function deleteBusinessUser(string $businessUserId): array
    {
        try {
            $result = $this->facebookBusiness->deleteBusinessUser($businessUserId);
            return $result;
        } catch (\Exception $e) {
            throw new AdvException(500, $e->getMessage(), $e, 0, $businessUserId, 'facebook', 'adv');
        }
    }
}
