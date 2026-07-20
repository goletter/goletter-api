# API 服务

## 安装

```
composer require goletter/hyperf-ai
```

## 配置
发布配置

```bash
php bin/hyperf.php vendor:publish goletter/hyperf-ai
```

### 自定义驱动

```php
$ai = \Goletter\Ai\Facade\Ai::driver('openai');

$response = $ai->transcribe($filePathOrUrl);
```