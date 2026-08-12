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
        $accounts = [
            ['code' => '1907426453234759', 'name' => 'YFPL-GF-0717+7-03'],
            ['code' => '966914535733264', 'name' => 'YFPL-EA-0720+8-01'],
            ['code' => '937050745619959', 'name' => 'YFPL-cd-0701+6-01'],
            ['code' => '946093961490991', 'name' => 'YFPL-cd-0701+6-02'],
            ['code' => '1480762437134528', 'name' => 'YFPL-EA-0720+8-03'],
            ['code' => '953232684338562', 'name' => 'YFPL-FM-0716+8-01'],
        ];

        $intro = "闲置账户提醒\n\n"
            . "为避免资源浪费，优化账户使用率，账户闲置时间大于15天，系统将自动进行回收；\n"
            . "如暂时不使用该账户，请前往账户管理页面提交回收。\n"
            . "感谢您的理解与支持！\n\n"
            . "以下为贵司【闲置3天及以上】账户清单：\n\n";

        $tableHeader = sprintf("%-17s %s\n", '账户ID', '账户名称');
        $table = $tableHeader;
        $messages = [];

        foreach ($accounts as $account) {
            $row = sprintf("%-17s %s\n", $account['code'], $account['name']);

            if (strlen($intro) + strlen($table) + strlen($row) > 3500 && $table !== $tableHeader) {
                $messages[] = $intro . '<pre>' . htmlspecialchars($table, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</pre>';
                $intro = '';
                $table = $tableHeader;
            }

            $table .= $row;
        }

        $messages[] = $intro . '<pre>' . htmlspecialchars($table, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</pre>';

        foreach ($messages as $message) {
            $bot->sendMessage([
                'chat_id' => $chatId,
                'text' => $message,
                'parse_mode' => 'HTML',
            ]);
        }
    }
}
