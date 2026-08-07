<?php

declare(strict_types=1);

namespace Goletter\Telegram\Contract;

use Goletter\Telegram\Update\Update;

interface BotInterface
{
    public function getToken(): string;

    public function getName(): string;

    /**
     * 调用任意 Bot API 方法，返回 result 字段。
     *
     * @param array<string, mixed> $params
     * @return mixed
     */
    public function call(string $method, array $params = []): mixed;

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    public function getMe(): array;

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    public function sendMessage(array $params): array;

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    public function editMessageText(array $params): array;

    /**
     * @param array<string, mixed> $params
     */
    public function deleteMessage(array $params): bool;

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    public function sendPhoto(array $params): array;

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    public function sendDocument(array $params): array;

    /**
     * @param array<string, mixed> $params
     */
    public function answerCallbackQuery(array $params): bool;

    /**
     * @param array<string, mixed> $params
     * @return list<array<string, mixed>>
     */
    public function getUpdates(array $params = []): array;

    /**
     * @param array<string, mixed> $params
     */
    public function setWebhook(array $params): bool;

    public function deleteWebhook(array $params = []): bool;

    /**
     * @return array<string, mixed>
     */
    public function getWebhookInfo(): array;

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    public function getFile(array $params): array;

    /**
     * 生成群主邀请链接（会吊销旧的主链接）。
     *
     * @param array{chat_id: int|string} $params
     */
    public function exportChatInviteLink(array $params): string;

    /**
     * 创建附加邀请链接（可限时、限人数、需审批）。
     *
     * @param array<string, mixed> $params
     * @return array<string, mixed> ChatInviteLink
     */
    public function createChatInviteLink(array $params): array;

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed> ChatInviteLink
     */
    public function editChatInviteLink(array $params): array;

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed> ChatInviteLink
     */
    public function revokeChatInviteLink(array $params): array;

    /**
     * 批准加群申请。
     *
     * @param array{chat_id: int|string, user_id: int} $params
     */
    public function approveChatJoinRequest(array $params): bool;

    /**
     * 拒绝加群申请。
     *
     * @param array{chat_id: int|string, user_id: int} $params
     */
    public function declineChatJoinRequest(array $params): bool;

    /**
     * @param array<string, mixed> $params
     */
    public function banChatMember(array $params): bool;

    /**
     * @param array<string, mixed> $params
     */
    public function unbanChatMember(array $params): bool;

    /**
     * @param array{chat_id: int|string, user_id: int} $params
     * @return array<string, mixed>
     */
    public function getChatMember(array $params): array;

    /**
     * @param array{chat_id: int|string} $params
     * @return list<array<string, mixed>>
     */
    public function getChatAdministrators(array $params): array;

    /**
     * @param array{chat_id: int|string} $params
     */
    public function getChatMemberCount(array $params): int;

    /**
     * @param array{chat_id: int|string} $params
     * @return array<string, mixed>
     */
    public function getChat(array $params): array;

    /**
     * 获取指定群成员信息。
     *
     * @return array<string, mixed> ChatMember
     */
    public function getGroupMember(int|string $chatId, int $userId): array;

    /**
     * 获取群管理员列表（Bot API 唯一可批量拿到的成员列表）。
     *
     * @return list<array<string, mixed>> ChatMember[]
     */
    public function getGroupAdmins(int|string $chatId): array;

    /**
     * 获取群人数。
     */
    public function getGroupMemberCount(int|string $chatId): int;

    /**
     * 按 user_id 列表批量查询群成员（并发安全：顺序请求）。
     *
     * Telegram Bot API **不支持**一次拉取全部群成员，只能查已知 user_id，
     * 或通过 chat_member 更新自行落库后再查。
     *
     * @param list<int> $userIds
     * @return array<int, array<string, mixed>|null> user_id => ChatMember|null（不在群内/失败为 null）
     */
    public function getGroupMembers(int|string $chatId, array $userIds): array;

    /**
     * 群用户概览：人数 + 管理员列表。
     *
     * @return array{chat_id: int|string, count: int, administrators: list<array<string, mixed>>}
     */
    public function getGroupUsers(int|string $chatId): array;

    /**
     * 便捷拉群：创建邀请链接并返回 invite_link 字符串。
     *
     * @param int|string $chatId
     * @param array{
     *     name?: string,
     *     expire_date?: int,
     *     member_limit?: int,
     *     creates_join_request?: bool
     * } $options
     * @return array<string, mixed> ChatInviteLink
     */
    public function inviteToChat(int|string $chatId, array $options = []): array;

    /**
     * 解析 Webhook 推送的 Update。
     *
     * @param array<string, mixed>|string $payload
     */
    public function parseUpdate(array|string $payload): Update;
}
