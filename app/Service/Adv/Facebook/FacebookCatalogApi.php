<?php

declare(strict_types=1);

namespace App\Service\Adv\Facebook;

use App\Service\Adv\AdvException;
use Goletter\Adv\Platforms\Facebook\FacebookCatalog;
use Goletter\Adv\Platforms\Facebook\FacebookClient;

/**
 * Facebook 商品目录
 */
class FacebookCatalogApi extends AdvException
{
    protected FacebookCatalog $facebookCatalog;

    public function __construct(FacebookClient $client)
    {
        $this->facebookCatalog = new FacebookCatalog($client);
    }

    /**
     * 查看目录在 BM 的权限级别
     * @param string $catalogId
     * @param array $fields
     * @return array
     */
    public function getCatalogAgencies(
        string $catalogId,
        array $fields = ['business', 'permitted_tasks', 'permitted_roles']
    ): array {
        try {
            $result = $this->facebookCatalog->listAgencies($catalogId, $fields);

            return $result;
        } catch (\Exception $e) {
            throw new AdvException(500, $e->getMessage(), $e, '', 0, 'facebook', 'adv');
        }
    }

    /**
     * 查询单个目录名称 / 行业
     * @param string $catalogId
     * @param array $fields
     * @return array
     */
    public function getCatalog(
        string $catalogId,
        array $fields = ['id', 'name', 'vertical']
    ): array {
        try {
            $result = $this->facebookCatalog->getCatalog($catalogId, $fields);

            return $result;
        } catch (\Exception $e) {
            throw new AdvException(500, $e->getMessage(), $e, '', 0, 'facebook', 'adv');
        }
    }

    /**
     * 获取 Business 下客户共享的商品目录
     * @param string $businessId
     * @param array $fields
     * @param int $limit
     * @return array
     */
    public function getClientProductCatalogs(
        string $businessId,
        array $fields = ['id', 'name', 'business', 'vertical'],
        int $limit = 1500
    ): array {
        try {
            $result = $this->facebookCatalog->listClientCatalogs($businessId, $fields, $limit);

            return $result;
        } catch (\Exception $e) {
            throw new AdvException(500, $e->getMessage(), $e, '', 0, 'facebook', 'adv');
        }
    }

    /**
     * 给人员分配 catalog 权限
     * @param string $catalogId
     * @param string $userId
     * @param array $tasks
     * @return array
     */
    public function addAssignedUser(string $catalogId, string $userId, array $tasks = ['MANAGE']): array
    {
        try {
            $result = $this->facebookCatalog->addAssignedUser($catalogId, $userId, $tasks);

            return $result;
        } catch (\Exception $e) {
            throw new AdvException(500, $e->getMessage(), $e, '', 0, 'facebook', 'adv');
        }
    }

    /**
     * 验证用户已被授权的目录
     * @param string $userId
     * @param array $fields
     * @return array
     */
    public function getAssignedProductCatalogs(
        string $userId,
        array $fields = ['id', 'name']
    ): array {
        try {
            $result = $this->facebookCatalog->listAssignedProductCatalogs($userId, $fields);

            return $result;
        } catch (\Exception $e) {
            throw new AdvException(500, $e->getMessage(), $e, '', 0, 'facebook', 'adv');
        }
    }
}
