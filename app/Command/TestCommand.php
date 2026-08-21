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

use App\Job\TestJob;
use Goletter\Server\Service\QueueService;
use Goletter\Telegram\Factory\BotFactory;
use Goletter\Telegram\Service\BotChatTracker;
use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Di\Annotation\Inject;

#[Command]
class TestCommand extends HyperfCommand
{
    #[Inject]
    protected BotFactory $bots;

    #[Inject]
    protected BotChatTracker $chats;

    #[Inject]
    private QueueService $queueService;

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
        /*$chatId = 8965689451;
        $token = '8631669243:AAFr8gijCSp1MlxJ5WQy98MapKTtz5sIhTs';
        $bot = $this->bots->token($token);
        // $updates = $bot->getUpdates(['limit' => 10]);
        // dd($updates);

        $chatId = '-1004388791491';
        // $users = $bot->getGroupUsers($chatId);
        $message = "闲置账户提醒\n\n"
            . "为避免资源浪费，优化账户使用率，账户闲置时间大于15天，系统将自动进行回收；\n"
            . "如暂时不使用该账户，请前往账户管理页面提交回收。\n"
            . "感谢您的理解与支持！\n\n"
            . "以下为贵司【闲置3天及以上】账户清单：\n\n";

        $message = $bot->sendMessage([
            'chat_id' => $chatId,
            'text' => $message,
        ]);*/

        /*
        $bot->deleteMessage([
            'chat_id' => $chatId,
            'message_id' => $message['message_id'],
        ]);*/

        $this->queueService->push(new TestJob(1), 'ms', 100);
    }
}
