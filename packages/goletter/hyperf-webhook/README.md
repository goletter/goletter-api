# goletter/hyperf-webhook

出站 Webhook HTTP 推送与 HMAC-SHA256 签名校验。业务「何时推、推什么、丢哪个队列」仍由应用层实现。

## 安装

在应用 `composer.json` 中 path autoload（与 hyperf-adv 相同）：

```json
"autoload": {
    "psr-4": {
        "Goletter\\Webhook\\": "packages/goletter/hyperf-webhook/src/"
    }
}
```

发布配置：

```bash
php bin/hyperf.php vendor:publish goletter/hyperf-webhook
```

## 出站推送

```php
use Goletter\Webhook\WebhookClient;

$result = $client->post($url, [
    'event' => 'account_demand.succeeded',
    'data' => [...],
    'occurred_at' => date('c'),
], $secret);

// $result['ok'] === true 表示对方返回 2xx
```

签名头默认 `X-Goletter-Signature: sha256=<hex>`，可用配置 `webhook.signature_header` 修改。

本包不负责「每租户 URL/密钥」存储；应用从平台拉取租户配置后再调用 `WebhookClient`。

## 入站验签

```php
use Goletter\Webhook\Signature;

$ok = Signature::verify($rawBody, $secret, $request->getHeaderLine('X-Goletter-Signature'));
```
