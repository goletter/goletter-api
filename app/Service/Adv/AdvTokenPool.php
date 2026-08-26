<?php

declare(strict_types=1);

namespace App\Service\Adv;

use Hyperf\Di\Annotation\Inject;
use Hyperf\Redis\Redis;

class AdvTokenPool
{
    #[Inject]
    protected Redis $redis;

    /**
     * 从轮询起点返回一整圈 token（Redis 原子自增，多 worker 安全）
     */
    public function rotate(string $platform, int $scopeId, array $tokens): array
    {
        $tokens = $this->filterTokens($tokens);
        if ($tokens === []) {
            return [];
        }

        $key = sprintf('adv:token_index:%s:%d', strtolower($platform), $scopeId);
        $count = count($tokens);
        $start = ($this->redis->incr($key) - 1) % $count;

        $ordered = [];
        for ($i = 0; $i < $count; ++$i) {
            $ordered[] = $tokens[($start + $i) % $count];
        }

        return $ordered;
    }

    /** 固定 token，不轮询 */
    public function fixed(?string $token): array
    {
        return $this->filterTokens([$token ?? '']);
    }

    /** 保底 token，按顺序尝试 */
    public function fallback(array $tokens): array
    {
        return $this->filterTokens($tokens);
    }

    private function filterTokens(array $tokens): array
    {
        return array_values(array_unique(array_filter(array_map(
            static fn ($token) => is_string($token) ? trim($token) : '',
            $tokens
        ))));
    }
}
