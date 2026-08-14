<?php

declare(strict_types=1);

namespace Goletter\Queue\Driver;

final class LuaScripts
{
    /**
     * Atomically move due members from a ZSET into a LIST (LPUSH).
     *
     * KEYS[1] = source zset (delayed / reserved)
     * KEYS[2] = destination list (waiting / timeout)
     * ARGV[1] = now in milliseconds
     * ARGV[2] = limit
     */
    public static function moveDue(): string
    {
        return <<<'LUA'
local from = KEYS[1]
local to = KEYS[2]
local now = ARGV[1]
local limit = tonumber(ARGV[2]) or 100
local jobs = redis.call('ZRANGEBYSCORE', from, '-inf', now, 'LIMIT', 0, limit)
local moved = 0
for _, job in ipairs(jobs) do
    if redis.call('ZREM', from, job) == 1 then
        redis.call('LPUSH', to, job)
        moved = moved + 1
    end
end
return moved
LUA;
    }
}
