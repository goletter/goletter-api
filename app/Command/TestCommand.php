<?php

declare(strict_types=1);

namespace App\Command;

use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Goletter\Telegram\Factory\BotFactory;
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
        $this->setDescription('测试 Google Sheets 创建、写入、归档与分享');
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

        $xx = $bot->sendMessage([
            'chat_id' => $chatId,
            'text' => '真正的强大，不是嘶吼着对抗全世界，而是在所有人都躺平吹风时，你敢咬碎牙，把被窝里那个懦弱的自己拎出来，按在书桌前。',
        ]);
        dd($xx);
    }
}
