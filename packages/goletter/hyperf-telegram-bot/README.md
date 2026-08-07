# Hyperf Telegram Bot

Hyperf 协程友好的 Telegram Bot API 客户端，支持多 Bot、Webhook 校验与常用消息能力。

## 安装

本仓库已通过 PSR-4 引入：

```json
"Goletter\\Telegram\\": "packages/goletter/hyperf-telegram-bot/src/"
```

并确保注解扫描包含：

```php
BASE_PATH . '/packages/goletter/hyperf-telegram-bot/src',
```

独立项目可通过 path repository 引入：

```bash
composer require goletter/hyperf-telegram-bot
php bin/hyperf.php vendor:publish goletter/hyperf-telegram-bot
```

## 配置

`config/autoload/telegram.php` 只需配 HTTP；大量机器人、Token 动态可变时**不必**写死在 `bots` 里：

```php
return [
    'default' => env('TELEGRAM_BOT', 'default'),
    'bots' => [
        // 可选静态 Bot；动态场景可留空
    ],
    'http' => [
        'base_uri' => env('TELEGRAM_API_BASE_URI', 'https://api.telegram.org'),
        'timeout' => (float) env('TELEGRAM_HTTP_TIMEOUT', 30),
        'proxy' => env('TELEGRAM_HTTP_PROXY'),
    ],
];
```

## 使用

### 动态多 Bot（推荐）

Token 来自数据库/业务配置，可随时变更。同名 Bot 在 Token 变化时会自动重建缓存。

```php
use Goletter\Telegram\Factory\BotFactory;
use Hyperf\Di\Annotation\Inject;

#[Inject]
protected BotFactory $bots;

// 按 Token 创建（默认用 bot_id 段做缓存名，如 123456:ABC -> bot:123456）
$bot = $this->bots->token($row->token);

// 推荐：用业务 ID 作为名称，便于 Token 轮换后命中同一缓存槽
$bot = $this->bots->token($row->token, name: (string) $row->id, options: [
    'webhook_secret' => (string) $row->webhook_secret,
]);

// Token 更新后再 resolve，旧实例自动失效
$bot = $this->bots->resolve((string) $row->id, $newToken, [
    'webhook_secret' => $newSecret,
]);

$bot->sendMessage([
    'chat_id' => 123456,
    'text' => 'Hello',
]);

// 主动丢弃缓存
$this->bots->forget((string) $row->id);
```

### 静态配置 Bot

若 `telegram.bots.xxx.token` 已配置：

```php
$bot = $this->bots->get('default');
```

### 发送文件

```php
$bot->sendDocument([
    'chat_id' => 123456,
    'document' => fopen('/path/to/file.pdf', 'r'),
    'caption' => '报表',
]);

// 或 SplFileInfo / ['contents' => ..., 'filename' => 'a.pdf']
```

### 获取群用户

Telegram Bot API **不能一次拉全群成员**，只能：

1. 查人数 / 管理员  
2. 按已知 `user_id` 查单个或批量  
3. 监听 `chat_member` 进退群事件自行落库后再查  

```php
$chatId = -1001234567890;

// 概览：人数 + 管理员
$users = $bot->getGroupUsers($chatId);
// ['chat_id' => ..., 'count' => 128, 'administrators' => [...]]

// 人数
$count = $bot->getGroupMemberCount($chatId);

// 管理员列表（可批量拿到的成员）
$admins = $bot->getGroupAdmins($chatId);

// 查单个成员
$member = $bot->getGroupMember($chatId, 123456789);
// $member['status'] => creator|administrator|member|restricted|left|kicked
// $member['user'] => [...]

// 按已知 user_id 批量查（不在群内的为 null）
$map = $bot->getGroupMembers($chatId, [111, 222, 333]);

// 群资料
$chat = $bot->getChat(['chat_id' => $chatId]);
```

监听成员变动并落库（Webhook 需允许 `chat_member`）：

```php
$bot->setWebhook([
    'url' => $url,
    'allowed_updates' => ['message', 'callback_query', 'chat_member', 'chat_join_request'],
]);

if ($changed = $update->getChatMemberUpdate()) {
    $user = $changed['new_chat_member']['user'];
    $status = $changed['new_chat_member']['status']; // member / left / ...
    // 写入你们的群成员表，之后用 getGroupMembers 回查
}
```

### 拉群 / 邀请链接

Bot **不能直接把用户拉进群**，需具备管理员的 `can_invite_users` 权限，通过邀请链接或审批加群申请完成。

```php
// 便捷创建邀请链接
$link = $bot->inviteToChat(-1001234567890, [
    'name' => '活动拉群',
    'member_limit' => 100,          // 可选：使用人数上限
    'expire_date' => time() + 86400, // 可选：过期时间
    // 'creates_join_request' => true, // 可选：需管理员审批
]);
// $link['invite_link'] => https://t.me/+xxxx

// 生成/重置主邀请链接
$url = $bot->exportChatInviteLink(['chat_id' => -1001234567890]);

// 审批加群申请（Webhook 收到 chat_join_request）
if ($join = $update->getChatJoinRequest()) {
    $bot->approveChatJoinRequest([
        'chat_id' => $update->getChatId(),
        'user_id' => $update->getUserId(),
    ]);
    // 或拒绝：$bot->declineChatJoinRequest([...]);
}

// 吊销链接
$bot->revokeChatInviteLink([
    'chat_id' => -1001234567890,
    'invite_link' => $link['invite_link'],
]);
```

### Webhook（动态 Bot）

路由建议带业务 ID：`POST /telegram/webhook/{id}`。

```php
use Goletter\Telegram\Factory\BotFactory;
use Goletter\Telegram\Helper\Webhook;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\PostMapping;
use Hyperf\HttpServer\Contract\RequestInterface;

#[Controller]
class TelegramWebhookController
{
    public function __construct(
        protected BotFactory $bots,
        protected Webhook $webhook,
        protected BotRepository $repo,
    ) {
    }

    #[PostMapping(path: '/telegram/webhook/{id}')]
    public function handle(int $id, RequestInterface $request)
    {
        $entity = $this->repo->find($id);

        $bot = $this->bots->token($entity->token, (string) $entity->id, [
            'webhook_secret' => $entity->webhook_secret,
        ]);

        $update = $this->webhook->parseRequest($request, $bot);

        if ($update->isCommand('start')) {
            $bot->sendMessage([
                'chat_id' => $update->getChatId(),
                'text' => 'Welcome!',
            ]);
        }

        return ['ok' => true];
    }
}
```

也可用 `Webhook::attach()` 挂到 Request，供中间件复用：

```php
$request = $this->webhook->attach($request, $entity->token, (string) $entity->id, [
    'webhook_secret' => $entity->webhook_secret,
]);
$update = $this->webhook->parseRequest($request);
$bot = $this->webhook->bot(null, $request);
```

设置 Webhook（每个机器人各自 URL）：

```php
$bot->setWebhook([
    'url' => 'https://example.com/telegram/webhook/' . $entity->id,
]);
```

### Long Polling

```php
$updates = $bot->getUpdates([
    'offset' => $offset,
    'timeout' => 30,
]);

foreach ($updates as $item) {
    $update = $bot->parseUpdate($item);
    // ...
}
```

## 异常

API 失败会抛出 `Goletter\Telegram\Exceptions\TelegramApiException`：

```php
try {
    $bot->sendMessage(['chat_id' => 1, 'text' => 'hi']);
} catch (\Goletter\Telegram\Exceptions\TelegramApiException $e) {
    $e->getDescription();
    $e->getErrorCode();
    $e->getParameters(); // 如 retry_after
    $e->getResponse();
}
```
