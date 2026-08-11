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

use Goletter\Telegram\Factory\BotFactory;
use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Di\Annotation\Inject;

#[Command]
class TestCommand extends HyperfCommand
{
    #[Inject]
    protected BotFactory $bots;

    public function __construct()
    {
        parent::__construct('test:to');
    }

    public function configure(): void
    {
        parent::configure();
        $this->setDescription('测试');
    }

    public function handle()
    {
        $chatId = 8965689451;
        $token = '8631669243:AAFr8gijCSp1MlxJ5WQy98MapKTtz5sIhTs';
        $bot = $this->bots->token($token);
        // $updates = $bot->getUpdates(['limit' => 10]);
        // dd($updates);
        $chatId = '-1004388791491';
        // $users = $bot->getGroupUsers($chatId);
        $message = '今天很难，明天更难，后天很美好。但大多数人死在明天晚上——你要做那个咬着牙看到后天太阳的人。';

        $bot->sendMessage([
            'chat_id' => $chatId,
            'text' => $message,
        ]);
    }
}
