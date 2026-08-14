# goletter/hyperf-queue

Millisecond-precision async queue for Hyperf, built on top of [`hyperf/async-queue`](https://github.com/hyperf/async-queue).

Official `RedisDriver` stores delay scores with `time()` (second precision). This package provides `RedisMsDriver` that:

- stores delayed / reserved scores in **milliseconds**
- exposes `pushMs()` / `Queue::laterMs()` / `dispatch_ms()`
- runs a lightweight mover coroutine (default every 5ms) so due jobs are pushed into `waiting` without waiting for `BRPOP` timeout
- uses a Lua script to move due jobs atomically (safe under multiple consumers)

## Requirements

- PHP >= 8.1
- Hyperf 3.1.x
- `hyperf/async-queue`
- Redis

## Install

```bash
composer require goletter/hyperf-queue
```

Path repository (monorepo):

```json
{
  "repositories": [
    {
      "type": "path",
      "url": "packages/goletter/hyperf-queue",
      "options": { "symlink": true }
    }
  ],
  "require": {
    "goletter/hyperf-queue": "*"
  }
}
```

Publish config (or merge into existing `config/autoload/async_queue.php`):

```bash
php bin/hyperf.php vendor:publish goletter/hyperf-queue
```

## Configuration

Keep the official `default` pool unchanged, and add a separate `ms` pool:

```php
use Goletter\Queue\Driver\RedisMsDriver;
use Hyperf\AsyncQueue\Driver\RedisDriver;

return [
    'default' => [
        'driver' => RedisDriver::class,
        // ... official second-based config
    ],
    'ms' => [
        'driver' => RedisMsDriver::class,
        'redis' => [
            'pool' => 'default',
        ],
        'channel' => '{queue-ms}',
        'timeout' => 2,
        'retry_milliseconds' => [100, 500, 1000, 3000],
        'handle_timeout' => 10,
        'move_interval_ms' => 5,
        'move_batch' => 200,
        'processes' => 1,
        'concurrent' => [
            'limit' => 10,
        ],
    ],
];
```

- Official jobs: existing `AsyncQueueConsumer` (`default` pool)
- Millisecond jobs: package process `Goletter\Queue\Process\MsQueueConsumer` (`ms` pool, auto-registered via `#[Process]`)

## Usage

```php
use App\Job\SendLetterJob;
use Goletter\Queue\Queue;
use function Goletter\Queue\dispatch_ms;
use function Hyperf\AsyncQueue\dispatch;

$job = new SendLetterJob(...);

// official second-based pool
dispatch($job);
dispatch($job, 5);
Queue::later(5, $job);

// millisecond pool (defaults to pool "ms")
Queue::laterMs(150, $job);
dispatch_ms($job, 150);
Queue::atMs((int) (microtime(true) * 1000) + 200, $job);
```

Jobs are normal `Hyperf\AsyncQueue\Job` classes — no special base class required.

## Ops

```bash
php bin/hyperf.php queue:ms-info
php bin/hyperf.php queue:ms-info ms
php bin/hyperf.php queue:ms-info default
```

## Production notes

1. **Do not share** `channel` between `RedisDriver` and `RedisMsDriver`. Score units differ (seconds vs milliseconds).
2. Prefer Redis Cluster hash tags in channel names, e.g. `{queue}`, so related keys stay in one slot.
3. `move_interval_ms` trades CPU vs delay accuracy. `5` is a good default (typical wake latency ≈ 5–20ms + Redis RTT).
4. Retry after failure uses `retry_milliseconds` (or `retry_seconds * 1000`).
5. Multi-tenant: put `tenantId` on the Job and restore context in `handle()`; rate-limit at push time if needed.

## How it works

```text
pushMs(150, job)
  → ZADD {channel}:delayed  score=now_ms+150

mover coroutine (every move_interval_ms)
  → Lua: due members → LPUSH {channel}:waiting

Consumer BRPOP waiting
  → reserved (ms score) → handle → ack / retry(ms) / fail
```

## License

MIT
