<?php
namespace App\Controller\Api\V1;

use App\Controller\AbstractController;
use Goletter\Docs\Google\Exceptions\GoogleApiException;
use Goletter\Docs\Google\GoogleAuth;
use Hyperf\Di\Annotation\Inject;

class GoogleController extends AbstractController
{
    #[Inject]
    protected GoogleAuth $auth;

    public function index()
    {
        return $this->success();
    }

    public function recharge()
    {
        return $this->success();
    }

    public function deduction()
    {
        return $this->success();
    }

    public function reset()
    {
        return $this->success();
    }

    public function bind()
    {
        return $this->success();
    }

    public function unbind()
    {
        return $this->success();
    }

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

        return $this->success([
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

        return $this->success([
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
