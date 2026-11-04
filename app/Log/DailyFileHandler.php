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

use DateTimeImmutable;
use DateTimeInterface;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\LogRecord;
use Monolog\Utils;

/**
 * 按日期直接追加到 YYYY-MM-DD.log，不做改名轮转。
 * 多个 Worker 同时 rename 同一个文件会把日志写花；追加写配合文件锁，一行一次 write。
 */
class DailyFileHandler extends StreamHandler
{
    private ?string $prunedOn = null;

    public function __construct(
        private readonly string $directory,
        int|Level|string $level = Level::Debug,
        private readonly int $maxFiles = 30,
        bool $useLocking = true,
    ) {
        $this->ensureDirectory();
        // 不设 filePermission：Windows bind mount / Docker 卷上 chmod 常失败并导致整行写日志抛错。
        parent::__construct($this->pathFor(new DateTimeImmutable()), $level, true, null, $useLocking);
    }

    protected function write(LogRecord $record): void
    {
        $path = $this->pathFor($record->datetime);
        if ($this->url !== $path) {
            $this->close();
            $this->ensureDirectory();
            $this->url = $path;
        } elseif (is_resource($this->stream) && ! is_file($path)) {
            // 外部删了日志文件（例如集成测试 tearDown）但 url 未变：必须关流重开，否则 fopen 仍写向已 unlink 的 inode。
            $this->close();
        }

        $this->ensureDirectory();
        if (! is_resource($this->stream) && ! is_file($path)) {
            // Windows Docker bind mount：空目录里容器侧首次 fopen 可能失败；先 touch 占位再交给 Monolog 追加。
            @touch($path);
        }

        parent::write($record);
        $this->prune($record->datetime);
    }

    private function pathFor(DateTimeInterface $date): string
    {
        return Utils::canonicalizePath(rtrim($this->directory, '/\\') . DIRECTORY_SEPARATOR . $date->format('Y-m-d') . '.log');
    }

    private function ensureDirectory(): void
    {
        if (is_dir($this->directory)) {
            return;
        }
        @mkdir($this->directory, 0775, true);
    }

    private function prune(DateTimeInterface $date): void
    {
        if ($this->maxFiles <= 0) {
            return;
        }
        $day = $date->format('Y-m-d');
        if ($this->prunedOn === $day) {
            return;
        }
        $this->prunedOn = $day;

        $files = glob(rtrim($this->directory, '/\\') . DIRECTORY_SEPARATOR . '*.log');
        if ($files === false || count($files) <= $this->maxFiles) {
            return;
        }

        $dated = [];
        foreach ($files as $file) {
            $name = basename($file);
            if (preg_match('/\A\d{4}-\d{2}-\d{2}\.log\z/', $name) === 1) {
                $dated[$name] = $file;
            }
        }
        ksort($dated);
        $excess = count($dated) - $this->maxFiles;
        if ($excess <= 0) {
            return;
        }
        foreach (array_slice($dated, 0, $excess) as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }
    }
}
