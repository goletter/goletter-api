<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Platform\PlatformClient;
use Goletter\Utils\TenantContext;
use Hyperf\Contract\ConfigInterface;
use Hyperf\HttpServer\Contract\ResponseInterface as HttpResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * 其它项目访问本子模块时的平台鉴权中间件。
 *
 * 支持：
 * - 用户/租户 Token：Authorization: Bearer xxx 或 X-Token
 * - 服务间调用：X-App-Id + X-App-Secret
 *
 * 向平台 verify 接口校验通过后，写入 tenant_id / user_id 等上下文。
 */
class PlatformAuthMiddleware implements MiddlewareInterface
{
    public function __construct(
        protected HttpResponse $response,
        protected PlatformClient $platform,
        protected ConfigInterface $config,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (! (bool) $this->config->get('platform.enable', true)) {
            return $handler->handle($request);
        }

        if (! $this->shouldAuthenticate($request)) {
            return $handler->handle($request);
        }

        $token = $this->bearerToken($request);
        $appId = $request->getHeaderLine('X-App-Id');
        $appSecret = $request->getHeaderLine('X-App-Secret');

        if (($token === null || $token === '') && ($appId === '' || $appSecret === '')) {
            return $this->unauthorized('Missing authorization credentials');
        }

        $auth = $this->platform->verify([
            'token' => $token ?? '',
            'app_id' => $appId,
            'app_secret' => $appSecret,
            'path' => $request->getUri()->getPath(),
            'method' => $request->getMethod(),
        ]);

        if (! ($auth['ok'] ?? false)) {
            return $this->unauthorized((string) ($auth['message'] ?? 'Platform auth failed'));
        }

        $tenantId = $auth['tenant_id'] ?? 0;
        TenantContext::set($tenantId);

        return $handler->handle(
            $request
                ->withAttribute('tenant_id', $tenantId)
                ->withAttribute('user_id', $auth['user_id'] ?? null)
                ->withAttribute('permissions', $auth['permissions'] ?? [])
                ->withAttribute('platform_auth', $auth)
        );
    }

    protected function shouldAuthenticate(ServerRequestInterface $request): bool
    {
        $path = $request->getUri()->getPath();

        if ($this->matchPatterns($path, (array) $this->config->get('platform.except', []))) {
            return false;
        }

        return $this->matchPrefixes($path, (array) $this->config->get('platform.only', ['/api']));
    }

    /**
     * @param string[] $prefixes
     */
    protected function matchPrefixes(string $path, array $prefixes): bool
    {
        foreach ($prefixes as $prefix) {
            $prefix = rtrim((string) $prefix, '/');
            if ($prefix === '') {
                continue;
            }
            if ($path === $prefix || str_starts_with($path, $prefix . '/')) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string[] $patterns 支持精确路径或前缀*
     */
    protected function matchPatterns(string $path, array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            $pattern = (string) $pattern;
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

    protected function unauthorized(string $message): ResponseInterface
    {
        return $this->response->json([
            'code' => 401,
            'message' => $message,
        ])->withStatus(401);
    }
}
