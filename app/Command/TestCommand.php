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
            'text' => '别抱怨生活太难，你见过凌晨四点的街道，可你见过凌晨四点还在搬砖的人吗？他们没时间抱怨，因为停下来，连砖都没得搬。',
        ]);
    }
}
