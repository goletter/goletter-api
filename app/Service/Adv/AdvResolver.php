<?php

declare(strict_types=1);

namespace App\Service\Adv;

use App\Model\Busines;
use App\Model\Platform;
use Goletter\Adv\Platforms\Facebook\Exceptions\FacebookTokenExpiredException;
use Goletter\Adv\Platforms\Google\Exceptions\GoogleTokenExpiredException;
use Goletter\Adv\Platforms\TikTok\Exceptions\TikTokTokenExpiredException;
use Hyperf\Collection\Arr;
use Hyperf\Di\Annotation\Inject;
use RuntimeException;
use Throwable;

class AdvResolver
{
    #[Inject]
    protected AdvTokenPool $tokenPool;

    /**
     * 固定 token 解析（开户、充值、授权等写操作）
     * token 为空且传入 busineId 时，自动取 BM 系统 token
     */
    public function resolveAdv(
        string $token = '',
        string $type = '',
        bool $isOauth = false,
        int $busineId = 0,
        int $platformId = 0,
    ): object {
        if ($token === '' && $busineId > 0) {
            $context = $this->buildTokenContext($type, $busineId, $isOauth, null);
            $token = (string) ($context['fixed'] ?? '');
            $platformId = $platformId > 0 ? $platformId : (int) ($context['platform_id'] ?? 0);
        }

        return AdvFactory::make($type, $token, $busineId, $platformId);
    }

    /**
     * 按策略执行第三方请求：轮询起点 + 失败切换 + 可选保底
     *
     * @param callable(object $service): mixed $callback
     */
    public function call(
        string $type,
        int $busineId,
        callable $callback,
        string $mode = AdvTokenMode::ROTATE,
        ?string $fixedToken = null,
        bool $useFallback = true,
        int $platformId = 0,
        bool $isOauth = false,
    ): mixed {
        $context = $this->buildTokenContext($type, $busineId, $isOauth, $fixedToken);
        $platformId = $platformId > 0 ? $platformId : (int) ($context['platform_id'] ?? 0);
        $scopeId = $busineId > 0 ? $busineId : $platformId;

        $candidates = match ($mode) {
            AdvTokenMode::FIXED => $this->tokenPool->fixed($fixedToken ?? $context['fixed']),
            AdvTokenMode::FALLBACK_ONLY => [],
            default => $this->tokenPool->rotate($type, $scopeId, $context['rotate']),
        };

        $result = $this->executeWithTokens($type, $busineId, $platformId, $candidates, $callback);
        if (! $result instanceof Throwable) {
            return $result;
        }
        $last = $result;

        if ($useFallback && $mode !== AdvTokenMode::FIXED) {
            $fallbackTokens = $this->tokenPool->fallback($context['fallback']);
            $result = $this->executeWithTokens($type, $busineId, $platformId, $fallbackTokens, $callback);
            if (! $result instanceof Throwable) {
                return $result;
            }
            $last = $result;
        }

        throw $last;
    }

    /**
     * @param callable(object $service): mixed $callback
     * @return mixed|Throwable 成功返回业务结果；全部失败返回最后一个异常
     */
    private function executeWithTokens(
        string $type,
        int $busineId,
        int $platformId,
        array $tokens,
        callable $callback,
    ): mixed {
        if ($tokens === []) {
            return new RuntimeException('无可用 token');
        }

        $last = null;

        foreach ($tokens as $token) {
            try {
                $service = AdvFactory::make($type, $token, $busineId, $platformId);

                return $callback($service);
            } catch (Throwable $e) {
                if (! $this->shouldFailover($e)) {
                    throw $e;
                }
                $last = $e;
            }
        }

        return $last ?? new RuntimeException('无可用 token');
    }

    /**
     * @return array{rotate: array<int, string>, fixed: ?string, fallback: array<int, string>, platform_id: int}
     */
    private function buildTokenContext(
        string $type,
        int $busineId,
        bool $isOauth,
        ?string $overrideToken,
    ): array {
        $platformKey = $this->normalizePlatform($type);
        $platformType = $this->platformKeyToType($platformKey);
        $fixed = $overrideToken;
        $platformId = 0;

        if ($busineId > 0) {
            $busine = Busines::query()->where('id', $busineId)->first();
            if ($busine) {
                $platformId = (int) Arr::get($busine, 'platform_id', 0);

                if (! $isOauth) {
                    $systemToken = (string) Arr::get($busine, 'token', '');
                    $systemUserId = (int) Arr::get($busine, 'system_user_id', 0);
                    $systemStatus = (int) Arr::get($busine, 'system_status', 0);
                    if ($systemToken !== '' && $systemUserId > 0 && $systemStatus > 0) {
                        $fixed = $fixed ?: $systemToken;
                    }
                }
            }
        }

        return [
            'rotate' => $this->loadPlatformTokens($platformId, $platformType, false),
            'fixed' => $fixed,
            'fallback' => $this->loadPlatformTokens($platformId, $platformType, true),
            'platform_id' => $platformId,
        ];
    }

    /**
     * 从 platforms 表加载 token 组
     * - 主池：同渠道、status=1、is_backup=0
     * - 保底：同渠道、status=1、is_backup=1
     */
    private function loadPlatformTokens(int $platformId, int|string $platformType, bool $isBackup): array
    {
        if ($platformId > 0) {
            $platform = Platform::query()->where('id', $platformId)->first();
            if ($platform) {
                $platformType = Arr::get($platform, 'type', $platformType);
            }
        }

        $query = Platform::query()->where('status', 1);

        if ($platformType !== '' && $platformType !== null) {
            $query->where('type', $platformType);
        }

        if ($isBackup) {
            $query->where('is_backup', 1);
        } else {
            $query->where(function ($builder) {
                $builder->where('is_backup', 0)->orWhereNull('is_backup');
            });
        }

        return $query->pluck('token')->filter()->unique()->values()->all();
    }

    private function normalizePlatform(string $type): string
    {
        if (is_numeric($type)) {
            return (string) Arr::get(['facebook', 'tiktok', 'google'], (int) $type, 'facebook');
        }

        $platform = strtolower($type);

        return match ($platform) {
            'fb', '0' => 'facebook',
            '1' => 'tiktok',
            '2' => 'google',
            default => $platform,
        };
    }

    private function platformKeyToType(string $platformKey): int|string
    {
        return match ($platformKey) {
            'facebook' => 0,
            'tiktok' => 1,
            'google' => 2,
            default => $platformKey,
        };
    }

    private function shouldFailover(Throwable $e): bool
    {
        if ($e instanceof FacebookTokenExpiredException
            || $e instanceof TikTokTokenExpiredException
            || $e instanceof GoogleTokenExpiredException) {
            return true;
        }

        $message = strtolower($e->getMessage());

        if (str_contains($message, 'rate limit')
            || str_contains($message, 'limit exceeded')
            || str_contains($message, 'too many calls')
            || str_contains($message, 'application request limit')) {
            return true;
        }

        if (str_contains($message, 'error validating access token')
            || str_contains($message, 'session has expired')
            || str_contains($message, 'invalid oauth access token')) {
            return true;
        }

        return false;
    }
}
