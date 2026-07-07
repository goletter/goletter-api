<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\GoogleAuthService;
use Hyperf\Di\Annotation\Inject;

class GoogleController extends AbstractController
{
    #[Inject]
    protected GoogleAuthService $authService;

    public function authUrl()
    {
        return $this->success([
            'auth_url' => $this->authService->getAuthUrl(),
        ]);
    }

    public function callback(GoogleAuthService $authService)
    {
        $code = $this->request->query('code');

        if (empty($code)) {
            return $this->fail(422, 'Missing authorization code');
        }
        $token = $authService->fetchToken($code);

        return $this->success(['token' => $token]);
    }
}
