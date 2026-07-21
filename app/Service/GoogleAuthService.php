<?php

declare(strict_types=1);

namespace App\Service;

use Goletter\Docs\Google\GoogleAuth;
use Google\Client;
use Hyperf\Di\Annotation\Inject;

class GoogleAuthService
{
    #[Inject]
    protected GoogleAuth $auth;

    public function getClient(): Client
    {
        return $this->auth->getClient();
    }

    /**
     * 生成授权 URL
     */
    public function getAuthUrl(): string
    {
        return $this->auth->getAuthUrl();
    }

    /**
     * 通过授权码换取访问令牌
     */
    public function fetchToken(string $code): array
    {
        return $this->auth->fetchToken($code);
    }

    /**
     * 使用刷新令牌刷新访问令牌
     */
    public function refreshToken(string $refreshToken): array
    {
        return $this->auth->refreshToken($refreshToken);
    }
}
