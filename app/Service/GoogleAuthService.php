<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace App\Service;

use Goletter\Server\Service\Service;
use Google\Client;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Di\Annotation\Inject;

class GoogleAuthService extends Service
{
    #[Inject]
    protected ConfigInterface $config;

    public function getClient(): Client
    {
        $client = new Client();

        // 从配置中读取认证信息
        $client->setClientId($this->config->get('google.client_id'));
        $client->setClientSecret($this->config->get('google.client_secret'));
        $client->setRedirectUri($this->config->get('google.redirect_uri'));
        $client->addScope(\Google_Service_Docs::DOCUMENTS);
        $client->addScope(\Google_Service_Drive::DRIVE_FILE);
        $client->setAccessType('offline');
        $client->setPrompt('consent');

        return $client;
    }

    /**
     * 生成授权 URL
     */
    public function getAuthUrl(): string
    {
        $client = $this->getClient();
        return $client->createAuthUrl();
    }

    /**
     * 通过授权码换取访问令牌
     */
    public function fetchToken(string $code): array
    {
        $client = $this->getClient();
        $client->authenticate($code);
        return $client->getAccessToken();
    }

    /**
     * 使用刷新令牌刷新访问令牌
     */
    public function refreshToken(string $refreshToken): array
    {
        $client = $this->getClient();
        $client->refreshToken($refreshToken);
        return $client->getAccessToken();
    }
}
