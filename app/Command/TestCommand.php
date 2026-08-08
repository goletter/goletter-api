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

        $bot->sendMessage([
            'chat_id' => $chatId,
            'text' => '一个人成熟的标志，就是不再把‘我很努力’挂在嘴边，而是把结果甩在桌上。在此之前，闭嘴，干活。',
        ]);
    }
}
