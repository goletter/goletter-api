<?php

declare(strict_types=1);

namespace Goletter\Adv\Platforms\Facebook;

use Goletter\Adv\Support\Arr;

/**
 * FaceBook 主页（Page）服务
 *
 * 对应 Graph API：
 * - /{page_id}
 * - /{business_id}/owned_pages
 * - /{business_id}/client_pages
 * - /{page_id}/assigned_users
 */
class FacebookPage
{
    public const DEFAULT_PAGE_FIELDS = [
        'id',
        'name',
        'category',
    ];

    public const DEFAULT_ASSIGNED_USER_FIELDS = [
        'id',
        'name',
        'tasks',
        'business',
    ];

    /** @var list<string> */
    public const PAGE_TASKS = [
        'MANAGE',
        'CREATE_CONTENT',
        'MODERATE',
        'ADVERTISE',
        'ANALYZE',
    ];

    public function __construct(
        protected FacebookClient $client
    ) {}

    /**
     * 获取主页详情
     * GET /{page_id}
     *
     * @param list<string> $fields
     */
    public function getPage(
        string $pageId,
        array $fields = self::DEFAULT_PAGE_FIELDS
    ): array {
        return $this->client->get("/{$pageId}", [
            'fields' => implode(',', $fields),
        ], 'GET:/{page_id}');
    }

    /**
     * BM 下自有主页列表
     * GET /{business_id}/owned_pages
     *
     * @param list<string> $fields
     */
    public function listOwnedPages(
        string $businessId,
        array $fields = self::DEFAULT_PAGE_FIELDS,
        int $limit = 500
    ): array {
        $pages = $this->client->getAll(
            "/{$businessId}/owned_pages",
            [
                'fields' => implode(',', $fields),
                'limit' => $limit,
            ],
            'GET:/{business_id}/owned_pages'
        );

        return Arr::uniqueBy($pages, 'id');
    }

    /**
     * 流式遍历自有主页
     *
     * @param list<string> $fields
     */
    public function iterateOwnedPages(
        string $businessId,
        array $fields = self::DEFAULT_PAGE_FIELDS,
        int $limit = 500
    ): \Generator {
        yield from Arr::uniqueGenerator(
            $this->client->paginate(
                "/{$businessId}/owned_pages",
                [
                    'fields' => implode(',', $fields),
                    'limit' => $limit,
                ],
                'GET:/{business_id}/owned_pages'
            ),
            'id'
        );
    }

    /**
     * BM 下客户端主页列表
     * GET /{business_id}/client_pages
     *
     * @param list<string> $fields
     */
    public function listClientPages(
        string $businessId,
        array $fields = self::DEFAULT_PAGE_FIELDS,
        int $limit = 500
    ): array {
        $pages = $this->client->getAll(
            "/{$businessId}/client_pages",
            [
                'fields' => implode(',', $fields),
                'limit' => $limit,
            ],
            'GET:/{business_id}/client_pages'
        );

        return Arr::uniqueBy($pages, 'id');
    }

    /**
     * 流式遍历客户端主页
     *
     * @param list<string> $fields
     */
    public function iterateClientPages(
        string $businessId,
        array $fields = self::DEFAULT_PAGE_FIELDS,
        int $limit = 500
    ): \Generator {
        yield from Arr::uniqueGenerator(
            $this->client->paginate(
                "/{$businessId}/client_pages",
                [
                    'fields' => implode(',', $fields),
                    'limit' => $limit,
                ],
                'GET:/{business_id}/client_pages'
            ),
            'id'
        );
    }

    /**
     * 主页已分配的 BM / 系统用户
     * GET /{page_id}/assigned_users?business={business_id}
     *
     * @param list<string> $fields
     */
    public function listAssignedUsers(
        string $pageId,
        string $businessId,
        array $fields = self::DEFAULT_ASSIGNED_USER_FIELDS,
        int $limit = 100
    ): array {
        return $this->client->getAll("/{$pageId}/assigned_users", [
            'business' => $businessId,
            'fields' => implode(',', $fields),
            'limit' => $limit,
        ], 'GET:/{page_id}/assigned_users');
    }

    /**
     * 流式遍历主页已分配用户
     *
     * @param list<string> $fields
     */
    public function iterateAssignedUsers(
        string $pageId,
        string $businessId,
        array $fields = self::DEFAULT_ASSIGNED_USER_FIELDS,
        int $limit = 100
    ): \Generator {
        yield from $this->client->paginate("/{$pageId}/assigned_users", [
            'business' => $businessId,
            'fields' => implode(',', $fields),
            'limit' => $limit,
        ], 'GET:/{page_id}/assigned_users');
    }

    /**
     * 给系统用户 / Business User 分配主页权限
     * POST /{page_id}/assigned_users
     *
     * @param list<string> $tasks MANAGE, CREATE_CONTENT, MODERATE, ADVERTISE, ANALYZE
     */
    public function assignUser(
        string $pageId,
        string $userId,
        string $businessId,
        array $tasks = ['ADVERTISE']
    ): array {
        $tasks = $this->normalizePageTasks($tasks);

        return $this->client->post("/{$pageId}/assigned_users", [
            'user' => $userId,
            'business' => $businessId,
            'tasks' => $tasks,
        ], [], 'POST:/{page_id}/assigned_users');
    }

    /**
     * 移除主页上的用户
     * DELETE /{page_id}/assigned_users
     */
    public function removeAssignedUser(
        string $pageId,
        string $userId,
        string $businessId
    ): array {
        return $this->client->delete("/{$pageId}/assigned_users", [
            'user' => $userId,
            'business' => $businessId,
        ], 'DELETE:/{page_id}/assigned_users');
    }

    // ---------- 兼容旧方法名（原 FacebookBusiness / 草稿类） ----------

    /** @deprecated 使用 listOwnedPages() */
    public function listPages(
        string $businessId,
        array $fields = self::DEFAULT_PAGE_FIELDS
    ): array {
        return $this->listOwnedPages($businessId, $fields);
    }

    /** @deprecated 使用 iterateOwnedPages() */
    public function iteratePages(
        string $businessId,
        array $fields = self::DEFAULT_PAGE_FIELDS
    ): \Generator {
        yield from $this->iterateOwnedPages($businessId, $fields);
    }

    /** @deprecated 使用 listAssignedUsers() */
    public function listPageAssignedUsers(
        string $pageId,
        string $businessId,
        array $fields = self::DEFAULT_ASSIGNED_USER_FIELDS,
        int $limit = 100
    ): array {
        return $this->listAssignedUsers($pageId, $businessId, $fields, $limit);
    }

    /** @deprecated 使用 iterateAssignedUsers() */
    public function iteratePageAssignedUsers(
        string $pageId,
        string $businessId,
        array $fields = self::DEFAULT_ASSIGNED_USER_FIELDS,
        int $limit = 100
    ): \Generator {
        yield from $this->iterateAssignedUsers($pageId, $businessId, $fields, $limit);
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

    /**
     * @param list<string> $tasks
     * @return list<string>
     */
    protected function normalizePageTasks(array $tasks): array
    {
        $tasks = array_values(array_unique(array_filter(array_map('strval', $tasks))));
        if ($tasks === []) {
            throw new \InvalidArgumentException('主页 tasks 不能为空');
        }
        foreach ($tasks as $task) {
            if (! in_array($task, self::PAGE_TASKS, true)) {
                throw new \InvalidArgumentException(
                    '无效的主页 task: ' . $task . '，可选 ' . implode(', ', self::PAGE_TASKS)
                );
            }
        }

        return $tasks;
    }
}
