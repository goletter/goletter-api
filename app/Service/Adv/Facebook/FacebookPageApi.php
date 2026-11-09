<?php

declare(strict_types=1);

namespace App\Service\Adv\Facebook;

use App\Service\Adv\AdvException;
use Goletter\Adv\Platforms\Facebook\FacebookClient;
use Goletter\Adv\Platforms\Facebook\FacebookPage;

/**
 * Facebook 主页（Page）服务
 */
class FacebookPageApi extends AdvException
{
    protected FacebookPage $facebookPage;

    public function __construct(FacebookClient $client)
    {
        $this->facebookPage = new FacebookPage($client);
    }

    /**
     * 获取主页详情
     *
     * @param list<string> $fields
     */
    public function getPage(
        string $pageId,
        array $fields = FacebookPage::DEFAULT_PAGE_FIELDS
    ): array {
        try {
            return $this->facebookPage->getPage($pageId, $fields);
        } catch (\Exception $e) {
            throw new AdvException(500, $e->getMessage(), $e, '', 0, 'facebook', 'adv');
        }
    }

    /**
     * BM 下自有主页列表
     *
     * @param list<string> $fields
     */
    public function listOwnedPages(
        string $businessId,
        array $fields = FacebookPage::DEFAULT_PAGE_FIELDS,
        int $limit = 500
    ): array {
        try {
            return $this->facebookPage->listOwnedPages($businessId, $fields, $limit);
        } catch (\Exception $e) {
            throw new AdvException(500, $e->getMessage(), $e, '', 0, 'facebook', 'adv');
        }
    }

    /**
     * BM 下客户端主页列表
     *
     * @param list<string> $fields
     */
    public function listClientPages(
        string $businessId,
        array $fields = FacebookPage::DEFAULT_PAGE_FIELDS,
        int $limit = 500
    ): array {
        try {
            return $this->facebookPage->listClientPages($businessId, $fields, $limit);
        } catch (\Exception $e) {
            throw new AdvException(500, $e->getMessage(), $e, '', 0, 'facebook', 'adv');
        }
    }

    /**
     * 主页已分配用户
     *
     * @param list<string> $fields
     */
    public function listAssignedUsers(
        string $pageId,
        string $businessId,
        array $fields = FacebookPage::DEFAULT_ASSIGNED_USER_FIELDS,
        int $limit = 100
    ): array {
        try {
            return $this->facebookPage->listAssignedUsers($pageId, $businessId, $fields, $limit);
        } catch (\Exception $e) {
            throw new AdvException(500, $e->getMessage(), $e, '', 0, 'facebook', 'adv');
        }
    }

    /**
     * 给系统用户 / Business User 分配主页权限
     *
     * @param list<string> $tasks
     */
    public function assignUser(
        string $pageId,
        string $userId,
        string $businessId,
        array $tasks = ['ADVERTISE']
    ): array {
        try {
            return $this->facebookPage->assignUser($pageId, $userId, $businessId, $tasks);
        } catch (\Exception $e) {
            throw new AdvException(500, $e->getMessage(), $e, '', 0, 'facebook', 'adv');
        }
    }

    /**
     * 移除主页已分配用户
     */
    public function removeAssignedUser(
        string $pageId,
        string $userId,
        string $businessId
    ): array {
        try {
            return $this->facebookPage->removeAssignedUser($pageId, $userId, $businessId);
        } catch (\Exception $e) {
            throw new AdvException(500, $e->getMessage(), $e, '', 0, 'facebook', 'adv');
        }
    }

    // ---------- 兼容旧方法名 ----------

    /** @deprecated 使用 listOwnedPages() */
    public function listPages(
        string $businessId,
        array $fields = FacebookPage::DEFAULT_PAGE_FIELDS
    ): array {
        return $this->listOwnedPages($businessId, $fields);
    }

    /** @deprecated 使用 listAssignedUsers() */
    public function listPageAssignedUsers(
        string $pageId,
        string $businessId,
        array $fields = FacebookPage::DEFAULT_ASSIGNED_USER_FIELDS,
        int $limit = 100
    ): array {
        return $this->listAssignedUsers($pageId, $businessId, $fields, $limit);
    }

    /** @deprecated 使用 assignUser() */
    public function assignPageUser(
        string $pageId,
        string $userId,
        string $businessId,
        array $tasks = ['ADVERTISE']
    ): array {
        return $this->assignUser($pageId, $userId, $businessId, $tasks);
    }

    /** @deprecated 使用 removeAssignedUser() */
    public function removePageAssignedUser(
        string $pageId,
        string $userId,
        string $businessId
    ): array {
        return $this->removeAssignedUser($pageId, $userId, $businessId);
    }
}
