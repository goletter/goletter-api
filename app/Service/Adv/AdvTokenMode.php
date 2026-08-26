<?php

declare(strict_types=1);

namespace App\Service\Adv;

class AdvTokenMode
{
    /** 主池轮询，失败后可选走保底 */
    public const ROTATE = 'rotate';

    /** 固定 token，不轮询 */
    public const FIXED = 'fixed';

    /** 跳过主池，仅使用保底 token */
    public const FALLBACK_ONLY = 'fallback_only';
}
