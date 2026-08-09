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

        $messages = [
            '你现在的状态：间歇性踌躇满志，持续性混吃等死。别不承认，大多数人都是这样。区别在于，有的人在‘间歇’的时候真的干了点事，而你在‘持续’的时候真的在等死。',
            "别说什么‘顺其自然’，那不过是‘无能为力’的遮羞布。真正的顺其自然，是竭尽全力之后的不强求，而不是两手一摊的不作为。",
            "你一个人吃饭、一个人加班、一个人回家、一个人刷手机到深夜。别觉得自己可怜，你只是在为未来的‘不一个人’攒底气。",
            "这世上没有真正的感同身受，你的崩溃在别人眼里可能只是一场无声的默剧。所以，别逢人就诉苦，要么自己消化，要么把它变成故事，等功成名就时再笑着讲出来。",
            "努力不一定成功，但不努力一定很舒服——舒服地焦虑着、舒服地后悔着、舒服地看着别人成功。你选哪种‘舒服’？",
            "别怕走得慢，只要不停下，乌龟也能赢兔子。但前提是，你不是那只在睡觉的兔子，也不是那只走两步就喊累的乌龟。",
            "跌倒了别急着爬起来，先在地上躺一会儿，想想是怎么倒的。但记住：只能躺一会儿。数到三，要么起来，要么被生活踩过去。",
            "你不是不行，你只是还没‘做完’。做完它，哪怕丑，哪怕错，哪怕被嘲笑。做完，你就赢了90%只敢想不敢做的人。"
        ];

        foreach ($messages as $message) {
            $bot->sendMessage([
                'chat_id' => $chatId,
                'text' => $message,
            ]);
        }
    }
}
