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

namespace App\Log;

use Hyperf\Contract\ConfigInterface;

/**
 * SQL 日志开关。默认只记慢查询，不把绑定参数拼进 SQL，也不落原文。
 */
final class SqlLogGate
{
    public function __construct(private ConfigInterface $config)
    {
    }

    public function shouldLog(float $timeMs): bool
    {
        if ($this->logAll()) {
            return true;
        }

        return $this->isSlow($timeMs);
    }

    public function isSlow(float $timeMs): bool
    {
        return $timeMs >= $this->slowMs();
    }

    public function logBindings(): bool
    {
        return (bool) $this->config->get('trace.sql.log_bindings', false);
    }

    private function logAll(): bool
    {
        return (bool) $this->config->get('trace.sql.log_all', false);
    }

    private function slowMs(): float
    {
        return (float) $this->config->get('trace.sql.slow_ms', 500);
    }
}
