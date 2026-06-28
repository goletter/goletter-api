<?php

declare(strict_types=1);

namespace App\Constants;

use Hyperf\Constants\Annotation\Constants;
use Hyperf\Constants\EnumConstantsTrait;

#[Constants]
enum LogTypeConstant: string
{
    use EnumConstantsTrait;

    /** 日常 / 通用 */
    const Daily = 'daily';

    /** 测试 */
    const Test = 'test';
}
