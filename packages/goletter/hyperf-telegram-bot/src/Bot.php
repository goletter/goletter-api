<?php

declare(strict_types=1);

namespace Goletter\Telegram;

use bot\src\Contract\BotInterface;
use bot\src\Exceptions\TelegramApiException;
use bot\src\Http\TelegramHttpClient;
use bot\src\Update\Update;
use GuzzleHttp\ClientInterface;

class Bot implements BotInterface
{
    protected TelegramHttpClient $http;

    public function __construct(
        protected string $token,
        ClientInterface $client,
        protected string $name = 'default',
        string $baseUri = 'https://api.telegram.org',
        protected string $webhookSecret = ''
    ) {
        $this->token = self::normalizeToken($token);
        $this->http = new TelegramHttpClient($client, $this->token, $baseUri);
    }

    /**
     * 规范化并校验 Bot Token（格式：123456789:AAH...）。
     */
    public static function normalizeToken(string $token): string
    {
        $token = trim($token);
        // 误把 URL 或带 bot 前缀的值整段塞进来时去掉前缀
        $token = preg_replace('#^https?://api\.telegram\.org/bot#i', '', $token) ?? $token;
        $token = preg_replace('#^bot#i', '', $token) ?? $token;
        $token = trim($token);

        if ($token === '') {
            throw new TelegramApiException('Telegram bot token is empty.');
        }

        // 合法格式：<bot_id>:<secret>，例如 7123456789:AAHxxxx
        if (! preg_match('/^\d+:[A-Za-z0-9_-]+$/', $token)) {
            throw new TelegramApiException(
                'Invalid Telegram bot token format. Expected "{bot_id}:{secret}", e.g. "7123456789:AAHxxxx". '
                . 'A bare secret without the numeric bot_id prefix will cause Telegram to respond with "Not Found".'
            );
        }

        return $token;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getWebhookSecret(): string
    {
        return $this->webhookSecret;
    }

    public function call(string $method, array $params = []): mixed
    {
        return $this->http->request($method, $params);
    }

    public function getMe(): array
    {
        return (array) $this->call('getMe');
    }

    public function sendMessage(array $params): array
    {
        return (array) $this->call('sendMessage', $params);
    }

    public function editMessageText(array $params): array
    {
        return (array) $this->call('editMessageText', $params);
    }

    public function deleteMessage(array $params): bool
    {
        return (bool) $this->call('deleteMessage', $params);
    }

    public function sendPhoto(array $params): array
    {
        return (array) $this->call('sendPhoto', $params);
    }

    public function sendDocument(array $params): array
    {
        return (array) $this->call('sendDocument', $params);
    }

    public function answerCallbackQuery(array $params): bool
    {
        return (bool) $this->call('answerCallbackQuery', $params);
    }

    public function getUpdates(array $params = []): array
    {
        $result = $this->call('getUpdates', $params);

        return is_array($result) ? $result : [];
    }

    public function setWebhook(array $params): bool
    {
        if ($this->webhookSecret !== '' && ! isset($params['secret_token'])) {
            $params['secret_token'] = $this->webhookSecret;
        }

        return (bool) $this->call('setWebhook', $params);
    }

    public function deleteWebhook(array $params = []): bool
    {
        return (bool) $this->call('deleteWebhook', $params);
    }

    public function getWebhookInfo(): array
    {
        return (array) $this->call('getWebhookInfo');
    }

    public function getFile(array $params): array
    {
        return (array) $this->call('getFile', $params);
    }

    public function exportChatInviteLink(array $params): string
    {
        return (string) $this->call('exportChatInviteLink', $params);
    }

    public function createChatInviteLink(array $params): array
    {
        return (array) $this->call('createChatInviteLink', $params);
    }

    public function editChatInviteLink(array $params): array
    {
        return (array) $this->call('editChatInviteLink', $params);
    }

    public function revokeChatInviteLink(array $params): array
    {
        return (array) $this->call('revokeChatInviteLink', $params);
    }

    public function approveChatJoinRequest(array $params): bool
    {
        return (bool) $this->call('approveChatJoinRequest', $params);
    }

    public function declineChatJoinRequest(array $params): bool
    {
        return (bool) $this->call('declineChatJoinRequest', $params);
    }

    public function banChatMember(array $params): bool
    {
        return (bool) $this->call('banChatMember', $params);
    }

    public function unbanChatMember(array $params): bool
    {
        return (bool) $this->call('unbanChatMember', $params);
    }

    public function getChatMember(array $params): array
    {
        return (array) $this->call('getChatMember', $params);
    }

    public function getChatAdministrators(array $params): array
    {
        $result = $this->call('getChatAdministrators', $params);

        return is_array($result) ? $result : [];
    }

    public function getChatMemberCount(array $params): int
    {
        return (int) $this->call('getChatMemberCount', $params);
    }

    public function getChat(array $params): array
    {
        return (array) $this->call('getChat', $params);
    }

    public function getGroupMember(int|string $chatId, int $userId): array
    {
        return $this->getChatMember([
            'chat_id' => $chatId,
            'user_id' => $userId,
        ]);
    }

    public function getGroupAdmins(int|string $chatId): array
    {
        return $this->getChatAdministrators([
            'chat_id' => $chatId,
        ]);
    }

    public function getGroupMemberCount(int|string $chatId): int
    {
        return $this->getChatMemberCount([
            'chat_id' => $chatId,
        ]);
    }

    public function getGroupMembers(int|string $chatId, array $userIds): array
    {
        $members = [];

        foreach ($userIds as $userId) {
            $id = (int) $userId;
            try {
                $members[$id] = $this->getGroupMember($chatId, $id);
            } catch (TelegramApiException) {
                $members[$id] = null;
            }
        }

        return $members;
    }

    public function getGroupUsers(int|string $chatId): array
    {
        return [
            'chat_id' => $chatId,
            'count' => $this->getGroupMemberCount($chatId),
            'administrators' => $this->getGroupAdmins($chatId),
        ];
    }

    /**
     * 便捷拉群：为指定群创建邀请链接。
     *
     * Bot 须为群管理员且具备 can_invite_users 权限。
     * Telegram Bot 无法直接把用户拉进群，需通过邀请链接或审批加群申请。
     */
    public function inviteToChat(int|string $chatId, array $options = []): array
    {
        return $this->createChatInviteLink(array_merge($options, [
            'chat_id' => $chatId,
        ]));
    }

    public function parseUpdate(array|string $payload): Update
    {
        if (is_string($payload)) {
            $decoded = json_decode($payload, true);
            if (! is_array($decoded)) {
                throw new TelegramApiException('Invalid Telegram update payload.');
            }
            $payload = $decoded;
        }

        return new Update($payload);
    }

    /**
     * 校验 Webhook Secret Token。
     */
    public function verifyWebhookSecret(?string $secretToken): bool
    {
        if ($this->webhookSecret === '') {
            return true;
        }

        return hash_equals($this->webhookSecret, (string) $secretToken);
    }
}
