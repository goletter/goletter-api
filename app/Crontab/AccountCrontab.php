<?php

namespace App\Crontab;

use Hyperf\Crontab\Annotation\Crontab;

#[Crontab(
    rule: '0 */1 * * * *',
    name: 'AccountCrontab',
    type: 'command',
    singleton: true,
    callback: [
        'command' => 'test:to',
        '--disable-event-dispatcher' => true,
    ],
    memo: '这是一个示例的定时任务',
    enable: true,   // 或仍写 enable: 'isEnable'
)]
class AccountCrontab
{
    public function isEnable(): bool
    {
        return false;
    }
}