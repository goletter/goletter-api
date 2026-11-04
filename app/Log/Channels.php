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

/**
 * 日志只开这几个目录。LoggerFactory::get($name) 的 $name 就是目录名，一天一个 YYYY-MM-DD.log。
 *
 * http   前端/HTTP：访问、参数响应、对外调用、这一跳的 span
 * queue  队列投递与消费
 * cli    命令行、定时任务
 * app    业务代码
 * sql    数据库（量大，单独放，避免冲淡业务）
 */
final class Channels
{
    public const HTTP = 'http';

    public const QUEUE = 'queue';

    public const CLI = 'cli';

    public const APP = 'app';

    public const SQL = 'sql';
}
