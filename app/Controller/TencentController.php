<?php

declare(strict_types=1);

namespace App\Controller;

use Goletter\Docs\Tencent\Exceptions\TencentApiException;
use Goletter\Docs\Tencent\TencentAuth;
use Hyperf\Di\Annotation\Inject;

class TencentController extends AbstractController
{
    #[Inject]
    protected TencentAuth $auth;

    /**
     * 获取腾讯文档 OAuth 授权地址.
     *
     * GET /api/tencent/auth-url
     */
    public function authUrl()
    {
        try {
            $url = $this->auth->getAuthUrl();
        } catch (TencentApiException $e) {
            $this->fail($e->getCode() ?: 400, $e->getMessage());
        }

        $this->success([
            'auth_url' => $url,
        ]);
    }

    /**
     * 腾讯文档 OAuth 回调，用 code 换取 token.
     *
     * GET /api/tencent/callback?code=xxx
     */
    public function callback()
    {
        $code = (string) $this->request->input('code', '');
        if ($code === '') {
            $this->fail(400, '缺少授权码 code');
        }

        $error = (string) $this->request->input('error', '');
        if ($error !== '') {
            $description = (string) $this->request->input('error_description', $error);

            $this->fail(400, $description);
        }

        try {
            $token = $this->auth->fetchToken($code);
        } catch (TencentApiException $e) {
            $this->fail($e->getCode() ?: 400, $e->getMessage());
        }

        $this->success([
            'access_token' => $token['access_token'] ?? null,
            'refresh_token' => $token['refresh_token'] ?? null,
            'expires_in' => $token['expires_in'] ?? null,
            'open_id' => $token['open_id'] ?? null,
            'user_id' => $token['user_id'] ?? null,
            'scope' => $token['scope'] ?? null,
            'token_type' => $token['token_type'] ?? null,
            'token' => $token,
        ]);
    }
}
