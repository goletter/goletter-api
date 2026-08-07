<?php

declare(strict_types=1);

namespace Goletter\Telegram\Helper;

use Goletter\Telegram\Bot;
use Goletter\Telegram\Exceptions\TelegramApiException;
use Goletter\Telegram\Factory\BotFactory;
use Goletter\Telegram\Update\Update;
use Psr\Http\Message\ServerRequestInterface;

class Webhook
{
    public const REQUEST_BOT_ATTRIBUTE = 'telegram.bot';

    public function __construct(protected BotFactory $botFactory)
    {
    }

    /**
     * 从 HTTP 请求解析 Update。
     *
     * @param Bot|string|null $bot Bot 实例、缓存名，或 null（从 request attribute / 默认配置解析）
     */
    public function parseRequest(
        ServerRequestInterface $request,
        Bot|string|null $bot = null,
        bool $verifySecret = true
    ): Update {
        $client = $this->resolveBot($request, $bot);

        if ($verifySecret) {
            $secret = $request->getHeaderLine('X-Telegram-Bot-Api-Secret-Token');
            if (! $client->verifyWebhookSecret($secret === '' ? null : $secret)) {
                throw new TelegramApiException('Invalid Telegram webhook secret token.', 403);
            }
        }

        return $client->parseUpdate((string) $request->getBody());
    }

    /**
     * @param Bot|string|null $bot
     */
    public function bot(Bot|string|null $bot = null, ?ServerRequestInterface $request = null): Bot
    {
        if ($bot instanceof Bot) {
            return $bot;
        }

        if (is_string($bot) && $bot !== '') {
            return $this->botFactory->get($bot);
        }

        if ($request instanceof ServerRequestInterface) {
            $attr = $request->getAttribute(self::REQUEST_BOT_ATTRIBUTE);
            if ($attr instanceof Bot) {
                return $attr;
            }
        }

        return $this->botFactory->get(is_string($bot) ? $bot : null);
    }

    /**
     * 用动态 Token 解析 Bot，并挂到 Request 上便于后续中间件/控制器使用。
     *
     * @param array{webhook_secret?: string} $options
     */
    public function attach(
        ServerRequestInterface $request,
        string $token,
        ?string $name = null,
        array $options = []
    ): ServerRequestInterface {
        $bot = $this->botFactory->token($token, $name, $options);

        return $request->withAttribute(self::REQUEST_BOT_ATTRIBUTE, $bot);
    }

    /**
     * @param Bot|string|null $bot
     */
    protected function resolveBot(ServerRequestInterface $request, Bot|string|null $bot): Bot
    {
        if ($bot instanceof Bot) {
            return $bot;
        }

        $attr = $request->getAttribute(self::REQUEST_BOT_ATTRIBUTE);
        if ($attr instanceof Bot) {
            return $attr;
        }

        if (is_string($bot) && $bot !== '') {
            return $this->botFactory->get($bot);
        }

        $routeBot = $request->getAttribute('bot');
        if (is_string($routeBot) && $routeBot !== '') {
            return $this->botFactory->get($routeBot);
        }

        return $this->botFactory->get();
    }
}
