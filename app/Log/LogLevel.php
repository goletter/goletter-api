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

use Monolog\Level;
use UnhandledMatchError;

final class LogLevel
{
    /**
     * LOG_LEVEL 环境变量合法取值（小写 Psr 名，与 Monolog\Level 一致，由低到高）。
     *
     * @return list<string>
     */
    public static function envChoices(): array
    {
        $choices = [];
        foreach (Level::cases() as $level) {
            $choices[] = $level->toPsrLogLevel();
        }

        return $choices;
    }

    /** 供 config / .env 注释引用的单行说明（与 envChoices() 同步）。 */
    public static function envChoicesHint(): string
    {
        return implode(' | ', self::envChoices());
    }

    public static function parse(string $name): Level
    {
        $name = strtolower(trim($name));
        if ($name === '') {
            return Level::Info;
        }

        try {
            return Level::fromName($name);
        } catch (UnhandledMatchError) {
            return Level::Info;
        }
    }

    /**
     * 严重度 >= $name 的全部 Psr\Log 级别名（stdout 打印白名单用）。
     *
     * vendor 的 StdoutLogger 收的是白名单数组（in_array 判定），这里把 LOG_LEVEL 阈值换算成白名单，
     * 与文件通道同一把尺子、同一兜底（非法/空值按 info）。级别名取自 Monolog\Level::toPsrLogLevel()
     * （小写，即 Psr\Log\LogLevel 常量值；注意 getName() 是大写，不可用于此处）。
     *
     * @return list<string>
     */
    public static function atLeast(string $name): array
    {
        $threshold = self::parse($name);

        $levels = [];
        foreach (Level::cases() as $level) {
            if ($level->value >= $threshold->value) {
                $levels[] = $level->toPsrLogLevel();
            }
        }

        return $levels;
    }
}
