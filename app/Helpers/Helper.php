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
use Carbon\Carbon;
use Hyperf\Amqp\Message\ProducerMessage;
use Hyperf\Amqp\Producer;

use function Goletter\Utils\di;

/**
 * 推送消息到 AMQP.
 */
function produce(ProducerMessage $message): bool
{
    return di()->get(Producer::class)->produce($message);
}

/**
 * @return Carbon
 */
function now()
{
    return Carbon::now();
}

function setOrderNo($class, $prefix = 'PO')
{
    $orderCount = $class::whereBetween('created_at', [now()->startOfDay(), now()->endOfDay()])->count();
    $orderCount = $orderCount + 1;

    return $prefix . substr((string) now()->year, 2) . date('md') . mt_rand(10000, 99999) . str_pad((string) $orderCount, 5, '0', STR_PAD_LEFT);
}

/**
 * 映射账户时区到 Google 文档格式
 * GMT -8:00 → ETC GMT MINUS 8
 * GMT +8:00 → ETC GMT PLUS 8
 * GMT -8:30 → ETC GMT MINUS 8:30.
 */
function mapGmtTimezoneToEtc(string $timezone): string
{
    $timezone = trim($timezone);
    if ($timezone === '') {
        return '';
    }

    if (! preg_match('/^GMT\s*([+-])\s*(\d{1,2})(?::(\d{2}))?$/i', $timezone, $m)) {
        return $timezone;
    }

    $sign = $m[1] === '-' ? 'MINUS' : 'PLUS';
    $hour = (int) $m[2];
    $minute = isset($m[3]) ? (int) $m[3] : 0;
    $offset = $minute === 0
        ? (string) $hour
        : sprintf('%d:%02d', $hour, $minute);

    return sprintf('ETC GMT %s %s', $sign, $offset);
}

function mapEtcToGmtTimezone(string $timezone): string
{
    $timezone = trim($timezone);
    if ($timezone === '') {
        return '';
    }

    if (! preg_match('/^ETC\s+GMT\s+(PLUS|MINUS)\s+(\d{1,2})(?::(\d{2}))?$/i', $timezone, $m)) {
        return $timezone;
    }

    $sign = strtoupper($m[1]) === 'MINUS' ? '-' : '+';
    $hour = (int) $m[2];
    $minute = isset($m[3]) ? (int) $m[3] : 0;

    return $minute === 0
        ? sprintf('GMT %s%d:00', $sign, $hour)
        : sprintf('GMT %s%d:%02d', $sign, $hour, $minute);
}
