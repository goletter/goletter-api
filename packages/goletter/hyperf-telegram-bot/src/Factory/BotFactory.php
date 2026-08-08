<?php

declare(strict_types=1);

namespace Goletter\Telegram\Factory;

use Goletter\Telegram\Bot;
use Goletter\Telegram\Exceptions\TelegramApiException;
use GuzzleHttp\ClientInterface;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Guzzle\ClientFactory;
use InvalidArgumentException;

/**
 * 支持配置文件与运行时动态 Token。
 *
 * 多机器人、Token 可变场景优先使用 token() / resolve()：
 *
 * ```php
 * $bot = $factory->token($row->token, name: (string) $row->id, options: [
 *     'webhook_secret' => $row->webhook_secret,
 * ]);
 * // Token 更新后再次 resolve 同名 Bot 会自动重建
 * $bot = $factory->resolve((string) $row->id, $row->token);
 * ```
 */
class BotFactory
{
    /**
     * @var array<string, Bot>
     */
    protected array $bots = [];

    /**
     * 缓存中的 token，用于检测变更。
     *
     * @var array<string, string>
     */
    protected array $tokens = [];

    protected ?ClientInterface $httpClient = null;

    public function __construct(
        protected ConfigInterface $config,
        protected ClientFactory $clientFactory
    ) {
    }

    /**
     * 按配置名获取 Bot（可被 register/resolve 覆盖）。
     */
    public function get(?string $name = null): Bot
    {
        $name = $name ?: (string) $this->config->get('telegram.default', 'default');

        if (isset($this->bots[$name])) {
            return $this->bots[$name];
        }

        $botConfig = (array) $this->config->get("telegram.bots.{$name}", []);
        $token = (string) ($botConfig['token'] ?? '');
        if ($token === '') {
            throw new TelegramApiException(sprintf(
                'Telegram bot [%s] token is not configured. Use token()/resolve() for dynamic bots.',
                $name
            ));
        }

        return $this->resolve($name, $token, [
            'webhook_secret' => (string) ($botConfig['webhook_secret'] ?? ''),
        ]);
    }

    /**
     * 使用动态 Token 创建（或复用）Bot。
     *
     * @param array{webhook_secret?: string, name?: string} $options
     */
    public function token(string $token, ?string $name = null, array $options = []): Bot
    {
        $token = trim($token);
        if ($token === '') {
            throw new TelegramApiException('Telegram bot token is empty.');
        }

        $name = $name ?? $this->nameFromToken($token);

        return $this->resolve($name, $token, $options);
    }

    /**
     * 按名称绑定动态 Token；Token 变化时自动丢弃旧实例。
     *
     * @param array{webhook_secret?: string} $options
     */
    public function resolve(string $name, string $token, array $options = []): Bot
    {
        $token = trim($token);
        if ($token === '') {
            throw new TelegramApiException(sprintf('Telegram bot [%s] token is empty.', $name));
        }

        if (isset($this->bots[$name], $this->tokens[$name]) && $this->tokens[$name] === $token) {
            $bot = $this->bots[$name];
            $secret = (string) ($options['webhook_secret'] ?? $bot->getWebhookSecret());
            if ($secret === $bot->getWebhookSecret()) {
                return $bot;
            }
        }

        $bot = $this->create($name, $token, $options);
        $this->bots[$name] = $bot;
        $this->tokens[$name] = $token;

        return $bot;
    }

    /**
     * 运行时注册/更新某个命名 Bot 的 Token。
     *
     * @param array{webhook_secret?: string} $options
     */
    public function register(string $name, string $token, array $options = []): Bot
    {
        return $this->resolve($name, $token, $options);
    }

    /**
     * 仅创建，不写入缓存（适合一次性调用）。
     *
     * @param array{webhook_secret?: string} $options
     */
    public function make(?string $name = null, ?string $token = null, array $options = []): Bot
    {
        $name = $name ?: (string) $this->config->get('telegram.default', 'default');

        if ($token === null || $token === '') {
            $botConfig = (array) $this->config->get("telegram.bots.{$name}", []);
            $token = (string) ($botConfig['token'] ?? '');
            if (! isset($options['webhook_secret'])) {
                $options['webhook_secret'] = (string) ($botConfig['webhook_secret'] ?? '');
            }
        }

        if ($token === '') {
            throw new TelegramApiException(sprintf('Telegram bot [%s] token is not configured.', $name));
        }

        return $this->create($name, $token, $options);
    }

    /**
     * @return list<string>
     */
    public function names(): array
    {
        $configured = $this->config->get('telegram.bots', []);
        $configured = is_array($configured) ? array_map('strval', array_keys($configured)) : [];

        return array_values(array_unique([...$configured, ...array_keys($this->bots)]));
    }

    public function has(string $name): bool
    {
        if (isset($this->bots[$name])) {
            return true;
        }

        return (string) $this->config->get("telegram.bots.{$name}.token", '') !== '';
    }

    public function forget(?string $name = null): void
    {
        if ($name === null) {
            $this->bots = [];
            $this->tokens = [];
            return;
        }

        unset($this->bots[$name], $this->tokens[$name]);
    }

    /**
     * 当前缓存中该名称对应的 Token（未缓存返回 null）。
     */
    public function cachedToken(string $name): ?string
    {
        return $this->tokens[$name] ?? null;
    }

    public function __call(string $method, array $arguments): mixed
    {
        $bot = $this->get();
        if (! method_exists($bot, $method)) {
            throw new InvalidArgumentException(sprintf('Method %s::%s does not exist.', Bot::class, $method));
        }

        return $bot->{$method}(...$arguments);
    }

    /**
     * @param array{webhook_secret?: string} $options
     */
    protected function create(string $name, string $token, array $options = []): Bot
    {
        $http = (array) $this->config->get('telegram.http', []);
        $baseUri = (string) ($http['base_uri'] ?? 'https://api.telegram.org');

        return new Bot(
            token: $token,
            client: $this->httpClient(),
            name: $name,
            baseUri: $baseUri,
            webhookSecret: (string) ($options['webhook_secret'] ?? '')
        );
    }

    protected function httpClient(): ClientInterface
    {
        if ($this->httpClient instanceof ClientInterface) {
            return $this->httpClient;
        }

        $http = (array) $this->config->get('telegram.http', []);
        $options = [
            'base_uri' => (string) ($http['base_uri'] ?? 'https://api.telegram.org'),
            'timeout' => (float) ($http['timeout'] ?? 30),
            'http_errors' => false,
        ];

        $proxy = $http['proxy'] ?? null;
        if (is_string($proxy) && $proxy !== '') {
            $options['proxy'] = $proxy;
        }

        return $this->httpClient = $this->clientFactory->create($options);
    }

    protected function nameFromToken(string $token): string
    {
        $botId = explode(':', $token, 2)[0] ?? '';
        if ($botId !== '' && ctype_digit($botId)) {
            return 'bot:' . $botId;
        }

        return 'token:' . hash('sha256', $token);
    }
}
