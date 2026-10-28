<?php

declare(strict_types=1);

namespace App\Middleware;

use Goletter\Utils\TenantContext;
use Hyperf\HttpServer\Contract\ResponseInterface as HttpResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * 鉴权中间件（全局注册）：
 * - 命中 $only 前缀 → 需要登录
 * - 命中 $except → 放行（登录、验证码等公开接口）
 * - 其它路径 → 不鉴权
 */
class AuthMiddleWare implements MiddlewareInterface
{
    /** 需要鉴权的路径前缀 */
    protected array $only = [
        // '/api/admin',
    ];

    /** 公开接口（即使在 $only 下也不鉴权），支持精确路径或前缀* */
    protected array $except = [
        '/api/admin/login',
        '/api/admin/captcha',
    ];

    public function __construct(protected HttpResponse $response)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (! $this->shouldAuthenticate($request)) {
            return $handler->handle($request);
        }

        $token = $this->bearerToken($request);
        if ($token === null || $token === '') {
            return $this->unauthorized('Missing authorization token');
        }

        $admin = $this->authenticate($token);
        if ($admin === null) {
            return $this->unauthorized('Invalid or expired token');
        }

        return $handler->handle(
            $request
                ->withAttribute('admin_id', $admin['id'])
                ->withAttribute('admin', $admin)
                ->withAttribute('tenant_id', $admin['tenant_id'] ?? 0)
        );
    }

    protected function shouldAuthenticate(ServerRequestInterface $request): bool
    {
        $path = $request->getUri()->getPath();

        if ($this->isExcept($path)) {
            return false;
        }

        foreach ($this->only as $prefix) {
            $prefix = rtrim($prefix, '/');
            if ($path === $prefix || str_starts_with($path, $prefix . '/')) {
                return true;
            }
        }

        return false;
    }

    protected function isExcept(string $path): bool
    {
        foreach ($this->except as $pattern) {
            if (str_ends_with($pattern, '*')) {
                $prefix = rtrim(substr($pattern, 0, -1), '/');
                if ($path === $prefix || str_starts_with($path, $prefix . '/')) {
                    return true;
                }
                continue;
            }

            if ($path === $pattern) {
                return true;
            }
        }

        return false;
    }

    protected function bearerToken(ServerRequestInterface $request): ?string
    {
        $header = $request->getHeaderLine('Authorization');
        if ($header !== '' && preg_match('/^Bearer\s+(\S+)/i', $header, $m)) {
            return $m[1];
        }

        $token = $request->getHeaderLine('X-Token');

        return $token !== '' ? $token : null;
    }

    /**
     * @return null|array{id: int, name: string, tenant_id?: int}
     */
    protected function authenticate(string $token): ?array
    {
        // TODO: 换成真实 Admin 鉴权
        if ($token === '') {
            return null;
        }

        return [
            'id' => 1,
            'name' => 'admin',
            'tenant_id' => 2,
        ];
    }

    protected function unauthorized(string $message): ResponseInterface
    {
        return $this->response->json([
            'code' => 401,
            'message' => $message,
        ])->withStatus(401);
    }
}
