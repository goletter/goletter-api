<?php

namespace App\Crontab;

use Hyperf\Crontab\Annotation\Crontab;

#[Crontab(
    rule: '0 */30 * * * *',
    name: 'GoogleTokenCrontab',
    type: 'command',
    singleton: true,
    callback: [
        'command' => 'goletter:google-token',
        '--disable-event-dispatcher' => true,
    ],
    memo: 'Google Token 刷新',
    enable: true,   // 或仍写 enable: 'isEnable'
)]
class GoogleTokenCrontab
{
    public function isEnable(): bool
    {
        return true;
    }
}