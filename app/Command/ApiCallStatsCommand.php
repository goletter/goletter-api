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

namespace App\Command;

use App\Log\Channels;
use Carbon\Carbon;
use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Symfony\Component\Console\Input\InputOption;

use function Hyperf\Support\env;

#[Command]
class ApiCallStatsCommand extends HyperfCommand
{
    public function __construct()
    {
        parent::__construct('goletter:api-call-stats');
    }

    public function configure(): void
    {
        parent::configure();
        $this->setDescription('按时间从日志统计第三方 API 调用耗时（次数/中位数/P90/P99/最长）');
        $this->addOption('from', null, InputOption::VALUE_REQUIRED, '开始时间，如 2026-11-01 或 2026-11-01 10:00:00');
        $this->addOption('to', null, InputOption::VALUE_REQUIRED, '结束时间（含），如 2026-11-06 或 2026-11-06 18:00:00');
        $this->addOption('message', null, InputOption::VALUE_OPTIONAL, '只统计指定 message；默认所有 *请求日志');
        $this->addOption('success', null, InputOption::VALUE_OPTIONAL, '1=仅成功 0=仅失败 空=全部', '');
        $this->addOption('log-dir', null, InputOption::VALUE_OPTIONAL, '日志目录，默认 LOG_PATH/app');
        $this->addOption('unit', null, InputOption::VALUE_OPTIONAL, '耗时单位：s|ms', 's');
    }

    public function handle(): void
    {
        $fromOpt = (string) $this->input->getOption('from');
        $toOpt = (string) $this->input->getOption('to');
        if ($fromOpt === '' || $toOpt === '') {
            $this->error('必须指定 --from 与 --to');
            return;
        }

        $from = $this->parseBound($fromOpt, false);
        $to = $this->parseBound($toOpt, true);
        if ($from->gt($to)) {
            $this->error('--from 不能晚于 --to');
            return;
        }

        $messageFilter = (string) ($this->input->getOption('message') ?? '');
        $successFilter = (string) ($this->input->getOption('success') ?? '');
        $unit = strtolower((string) ($this->input->getOption('unit') ?? 's'));
        if (! in_array($unit, ['s', 'ms'], true)) {
            $this->error('--unit 仅支持 s 或 ms');
            return;
        }

        $logDir = (string) ($this->input->getOption('log-dir') ?? '');
        if ($logDir === '') {
            $base = rtrim((string) (env('LOG_PATH') ?: BASE_PATH . '/runtime/logs'), '/\\');
            $logDir = $base . DIRECTORY_SEPARATOR . Channels::APP;
        }

        /** @var array<string, list<float>> $buckets key = message\tapi_interface */
        $buckets = [];
        $files = 0;
        $lines = 0;
        $matched = 0;

        for ($day = $from->copy()->startOfDay(); $day->lte($to); $day->addDay()) {
            $file = rtrim($logDir, '/\\') . DIRECTORY_SEPARATOR . $day->format('Y-m-d') . '.log';
            if (! is_file($file)) {
                continue;
            }
            ++$files;
            $fh = fopen($file, 'rb');
            if ($fh === false) {
                $this->warn('无法打开: ' . $file);
                continue;
            }
            while (($line = fgets($fh)) !== false) {
                ++$lines;
                $row = json_decode($line, true);
                if (! is_array($row)) {
                    continue;
                }
                $message = (string) ($row['message'] ?? '');
                if (! $this->isCallLogMessage($message, $messageFilter)) {
                    continue;
                }
                $at = $this->rowTime($row);
                if ($at === null || $at->lt($from) || $at->gt($to)) {
                    continue;
                }
                if ($successFilter !== '' && (string) ($row['is_success'] ?? '') !== $successFilter) {
                    continue;
                }
                $iface = (string) ($row['api_interface'] ?? '');
                if ($iface === '' || ! isset($row['duration_ms'])) {
                    continue;
                }
                $ms = (float) $row['duration_ms'];
                $key = $message . "\t" . $iface;
                $buckets[$key][] = $ms;
                ++$matched;
            }
            fclose($fh);
        }

        if ($matched === 0) {
            $this->warn(sprintf(
                '未找到匹配日志（目录=%s，文件数=%d，扫描行=%d，区间=%s ~ %s）',
                $logDir,
                $files,
                $lines,
                $from->toDateTimeString(),
                $to->toDateTimeString()
            ));
            return;
        }

        $rows = [];
        foreach ($buckets as $key => $durations) {
            sort($durations, SORT_NUMERIC);
            [$message, $iface] = explode("\t", $key, 2);
            $n = count($durations);
            $rows[] = [
                'message' => $message,
                'api_interface' => $iface,
                'count' => $n,
                'p50' => $this->percentile($durations, 0.50),
                'p90' => $this->percentile($durations, 0.90),
                'p99' => $this->percentile($durations, 0.99),
                'max' => $durations[$n - 1],
            ];
        }

        usort($rows, static function (array $a, array $b): int {
            return $b['count'] <=> $a['count']
                ?: strcmp($a['message'], $b['message'])
                ?: strcmp($a['api_interface'], $b['api_interface']);
        });

        $this->info(sprintf(
            '区间 %s ~ %s | 文件 %d | 扫描 %d 行 | 命中 %d 次 | 接口 %d 个',
            $from->toDateTimeString(),
            $to->toDateTimeString(),
            $files,
            $lines,
            $matched,
            count($rows)
        ));

        $this->table(
            ['来源', '接口', '次数', '中位数', '90%', '99%', '最长'],
            array_map(function (array $r) use ($unit): array {
                return [
                    $r['message'],
                    $r['api_interface'],
                    $r['count'],
                    $this->formatDuration($r['p50'], $unit),
                    $this->formatDuration($r['p90'], $unit),
                    $this->formatDuration($r['p99'], $unit),
                    $this->formatDuration($r['max'], $unit),
                ];
            }, $rows)
        );
    }

    private function parseBound(string $value, bool $endOfDayIfDateOnly): Carbon
    {
        $carbon = Carbon::parse($value);
        // 仅日期时：from 取当天 00:00:00，to 取当天 23:59:59
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', trim($value)) === 1) {
            return $endOfDayIfDateOnly ? $carbon->endOfDay() : $carbon->startOfDay();
        }

        return $carbon;
    }

    private function isCallLogMessage(string $message, string $messageFilter): bool
    {
        if ($messageFilter !== '') {
            return $message === $messageFilter;
        }
        // 默认：facebook请求日志 / tiktok请求日志 等；排除失败兜底的「接口日志」以免双计
        return $message !== '' && str_ends_with($message, '请求日志');
    }

    /**
     * @param array<string, mixed> $row
     */
    private function rowTime(array $row): ?Carbon
    {
        if (! empty($row['created_at']) && is_string($row['created_at'])) {
            return Carbon::parse($row['created_at']);
        }
        if (! empty($row['@timestamp']) && is_string($row['@timestamp'])) {
            return Carbon::parse($row['@timestamp']);
        }

        return null;
    }

    /**
     * @param list<float> $sorted 已升序
     */
    private function percentile(array $sorted, float $p): float
    {
        $n = count($sorted);
        if ($n === 0) {
            return 0.0;
        }
        if ($n === 1) {
            return $sorted[0];
        }
        $idx = ($n - 1) * $p;
        $lo = (int) floor($idx);
        $hi = (int) ceil($idx);
        if ($lo === $hi) {
            return $sorted[$lo];
        }
        $w = $idx - $lo;

        return $sorted[$lo] * (1 - $w) + $sorted[$hi] * $w;
    }

    private function formatDuration(float $ms, string $unit): string
    {
        if ($unit === 'ms') {
            return sprintf('%.0fms', $ms);
        }
        if ($ms < 100) {
            return '< 0.1s';
        }

        return sprintf('%.1fs', $ms / 1000);
    }
}
