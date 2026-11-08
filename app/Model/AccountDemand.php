<?php
declare(strict_types=1);

namespace App\Model;

use Goletter\ModelFilter\Filterable;

class AccountDemand extends Model
{
    use Filterable;

    // === STEP START（由生成器生成，请勿手改）===
    // 步骤:0|step_zero=待审核,1|step_one=待分配,2|step_two=待绑卡
    public const int STEP_ZERO = 0;
    public const int STEP_ONE = 1;
    public const int STEP_TWO = 2;

    public const array STEP_LABELS = [
        self::STEP_ZERO => '待审核',
        self::STEP_ONE => '待分配',
        self::STEP_TWO => '待绑卡',
    ];

    public const array STEP_ALL = [
        self::STEP_ZERO,
        self::STEP_ONE,
        self::STEP_TWO,
    ];
    // === STEP END ===

    // === STATUS START（由生成器生成，请勿手改）===
    // 状态:1|online=待处理,2|offline=下线
    public const int STATUS_ONLINE = 1;
    public const int STATUS_OFFLINE = 2;

    public const array STATUS_LABELS = [
        self::STATUS_ONLINE => '待处理',
        self::STATUS_OFFLINE => '下线',
    ];

    public const array STATUS_ALL = [
        self::STATUS_ONLINE,
        self::STATUS_OFFLINE,
    ];
    // === STATUS END ===
}
