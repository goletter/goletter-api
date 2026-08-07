<?php

declare(strict_types=1);

namespace Goletter\Telegram\Exceptions;

use RuntimeException;
use Throwable;

class TelegramApiException extends RuntimeException
{
    /**
     * @param array<string, mixed> $response
     */
    public function __construct(
        string $message = '',
        int $code = 0,
        protected array $response = [],
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return array<string, mixed>
     */
    public function getResponse(): array
    {
        return $this->response;
    }

    public function getDescription(): string
    {
        return (string) ($this->response['description'] ?? $this->getMessage());
    }

    public function getErrorCode(): int
    {
        return (int) ($this->response['error_code'] ?? $this->getCode());
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getParameters(): ?array
    {
        $parameters = $this->response['parameters'] ?? null;

        return is_array($parameters) ? $parameters : null;
    }
}
