<?php

declare(strict_types=1);

namespace Goletter\Telegram\Update;

use ArrayAccess;
use JsonSerializable;

/**
 * Telegram Update 轻量封装，支持数组访问。
 *
 * @implements ArrayAccess<string, mixed>
 */
class Update implements ArrayAccess, JsonSerializable
{
    /**
     * @param array<string, mixed> $data
     */
    public function __construct(protected array $data)
    {
    }

    public function getUpdateId(): int
    {
        return (int) ($this->data['update_id'] ?? 0);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getMessage(): ?array
    {
        return $this->firstOf(['message', 'edited_message', 'channel_post', 'edited_channel_post']);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getCallbackQuery(): ?array
    {
        $query = $this->data['callback_query'] ?? null;

        return is_array($query) ? $query : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getChatJoinRequest(): ?array
    {
        $request = $this->data['chat_join_request'] ?? null;

        return is_array($request) ? $request : null;
    }

    /**
     * 成员状态变更（进群/退群/权限变化）。
     *
     * @return array<string, mixed>|null ChatMemberUpdated
     */
    public function getChatMemberUpdate(): ?array
    {
        return $this->firstOf(['chat_member', 'my_chat_member']);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getChat(): ?array
    {
        $message = $this->getMessage();
        if (isset($message['chat']) && is_array($message['chat'])) {
            return $message['chat'];
        }

        $callback = $this->getCallbackQuery();
        if (isset($callback['message']['chat']) && is_array($callback['message']['chat'])) {
            return $callback['message']['chat'];
        }

        $joinRequest = $this->getChatJoinRequest();
        if (isset($joinRequest['chat']) && is_array($joinRequest['chat'])) {
            return $joinRequest['chat'];
        }

        $memberUpdate = $this->getChatMemberUpdate();
        if (isset($memberUpdate['chat']) && is_array($memberUpdate['chat'])) {
            return $memberUpdate['chat'];
        }

        return null;
    }

    public function getChatId(): int|string|null
    {
        $chat = $this->getChat();

        return $chat['id'] ?? null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getFrom(): ?array
    {
        $message = $this->getMessage();
        if (isset($message['from']) && is_array($message['from'])) {
            return $message['from'];
        }

        $callback = $this->getCallbackQuery();
        if (isset($callback['from']) && is_array($callback['from'])) {
            return $callback['from'];
        }

        $joinRequest = $this->getChatJoinRequest();
        if (isset($joinRequest['from']) && is_array($joinRequest['from'])) {
            return $joinRequest['from'];
        }

        $memberUpdate = $this->getChatMemberUpdate();
        if (isset($memberUpdate['from']) && is_array($memberUpdate['from'])) {
            return $memberUpdate['from'];
        }
        if (isset($memberUpdate['new_chat_member']['user']) && is_array($memberUpdate['new_chat_member']['user'])) {
            return $memberUpdate['new_chat_member']['user'];
        }

        return null;
    }

    public function getUserId(): ?int
    {
        $from = $this->getFrom();
        if ($from === null || ! isset($from['id'])) {
            return null;
        }

        return (int) $from['id'];
    }

    public function getText(): ?string
    {
        $message = $this->getMessage();
        if (isset($message['text']) && is_string($message['text'])) {
            return $message['text'];
        }

        $callback = $this->getCallbackQuery();
        if (isset($callback['data']) && is_string($callback['data'])) {
            return $callback['data'];
        }

        return null;
    }

    public function isCommand(?string $command = null): bool
    {
        $text = $this->getText();
        if ($text === null || ! str_starts_with($text, '/')) {
            return false;
        }

        if ($command === null) {
            return true;
        }

        $name = ltrim($command, '/');
        $parts = preg_split('/\s+/', $text, 2) ?: [];
        $first = (string) ($parts[0] ?? '');
        $first = explode('@', $first, 2)[0];

        return $first === '/' . $name;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->data;
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->data[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->data[$offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if ($offset === null) {
            $this->data[] = $value;
            return;
        }

        $this->data[$offset] = $value;
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->data[$offset]);
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->data;
    }

    /**
     * @param list<string> $keys
     * @return array<string, mixed>|null
     */
    protected function firstOf(array $keys): ?array
    {
        foreach ($keys as $key) {
            $value = $this->data[$key] ?? null;
            if (is_array($value)) {
                return $value;
            }
        }

        return null;
    }
}
