<?php

declare(strict_types=1);

namespace App\Controller;

use Goletter\Docs\Google\Exceptions\GoogleApiException;
use Goletter\Docs\Google\GoogleAuth;
use Hyperf\Di\Annotation\Inject;

class GoogleController extends AbstractController
{
    #[Inject]
    protected GoogleAuth $auth;

    /**
     * 获取 Google OAuth 授权地址.
     *
     * GET /api/google/auth-url
     */
    public function authUrl()
    {
        try {
            $url = $this->auth->getAuthUrl();
        } catch (GoogleApiException $e) {
           $this->fail($e->getCode() ?: 400, $e->getMessage());
        }

        $this->success([
            'auth_url' => $url,
        ]);
    }

    /**
     * Google OAuth 回调，用 code 换取 token.
     *
     * GET /api/google/callback?code=xxx
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
        } catch (GoogleApiException $e) {
            $this->fail($e->getCode() ?: 400, $e->getMessage());
        }

        $this->success([
            'access_token' => $token['access_token'] ?? null,
            'refresh_token' => $token['refresh_token'] ?? null,
            'expires_in' => $token['expires_in'] ?? null,
            'created' => $token['created'] ?? null,
            'scope' => $token['scope'] ?? null,
            'token_type' => $token['token_type'] ?? null,
            'token' => $token,
        ]);
    }
}
