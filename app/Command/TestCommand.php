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
            ['code' => '1453977263132132', 'name' => 'YFPL-ve-vo-0810+8-02'],
            ['code' => '2421788211621309', 'name' => 'YFPL-FM-VO-0728-3-40'],
            ['code' => '1439209274339798', 'name' => 'YFPL-FM-VO-0728-3-39'],
            ['code' => '3982717118695216', 'name' => 'YFPL-FM-VO-0728-3-38'],
            ['code' => '709027018887090', 'name' => 'YFPL-FM-VO-0728+8-20'],
            ['code' => '906897005446475', 'name' => 'YFPL-GF-VO-0728-3-051'],
            ['code' => '1421886693313610', 'name' => 'YFPL-fg-vo-0731-6-06'],
            ['code' => '2473868819795197', 'name' => 'YFPL-fg-vo-0731-6-05'],
            ['code' => '1698491637835187', 'name' => 'YFPL-fg-vo-0731-6-04'],
            ['code' => '1645345249791350', 'name' => 'YFPL-fm-vo-0724-6-01'],
            ['code' => '1821425265836962', 'name' => 'YFPL-fm-vo-0724+8-14'],
            ['code' => '912493391665770', 'name' => 'YFPL-fm-vo-0724-6-05'],
            ['code' => '2108124706728891', 'name' => 'YFPL-fg-vo-0727+7-09'],
            ['code' => '906159568929808', 'name' => 'YFPL-fm-vo-0803+7-50'],
            ['code' => '1423125836025728', 'name' => 'YFPL-fm-vo-0803+7-41'],
            ['code' => '1441495324338066', 'name' => 'YFPL-fm-vo-0803+7-40'],
            ['code' => '2220568778749272', 'name' => 'YFPL-fm-vo-0803+7-39'],
            ['code' => '949108177472114', 'name' => 'YFPL-fm-vo-0803+7-10'],
            ['code' => '1245198590439948', 'name' => 'YFPL-fm-vo-0803+7-09'],
            ['code' => '1571701254158793', 'name' => 'YFPL-fm-vo-0803+7-04'],
            ['code' => '944015651716564', 'name' => 'YFPL-fm-vo-0727+8-18'],
            ['code' => '1486020609573959', 'name' => 'QQPL-FG-0706+7-09'],
            ['code' => '4232435353692182', 'name' => 'YFPL-fm-vo-0724+8-13'],
            ['code' => '1300183294977050', 'name' => 'YFPL-fg-vo-0731-6-02'],
            ['code' => '748842004655876', 'name' => 'YFPL-fg-vo-0731-6-01'],
            ['code' => '966914535733264', 'name' => 'YFPL-EA-0720+8-01'],
            ['code' => '3649275192033265', 'name' => 'YFPL-gf-vo-0804+7-01'],
            ['code' => '807590218884368', 'name' => 'YFPL-fg-vo-0803+7-05'],
            ['code' => '25521686374099511', 'name' => 'YFPL-fg-vo-0803+7-04'],
            ['code' => '1123847402502520', 'name' => 'YFPL-fg-vo-0803+7-03'],
            ['code' => '2352588482153173', 'name' => 'YFPL-fg-vo-0803+7-02'],
            ['code' => '1123658965887069', 'name' => 'YFPL-fg-vo-0803+7-01'],
            ['code' => '2168717616972251', 'name' => 'YFPL-fg-vo-0731-6-03'],
            ['code' => '4322416894682868', 'name' => 'YFPL-fg-vo-0804+7-25'],
            ['code' => '4322484227999297', 'name' => 'YFPL-fg-vo-0804+7-24'],
            ['code' => '4324182024460284', 'name' => 'YFPL-fg-vo-0804+7-23'],
            ['code' => '4329061707414906', 'name' => 'YFPL-fg-vo-0804+7-22'],
            ['code' => '4330271197187137', 'name' => 'YFPL-fg-vo-0804+7-21'],
            ['code' => '4444028272497700', 'name' => 'YFPL-FM-VO-0728-3-32'],
            ['code' => '1253973352883780', 'name' => 'YFPL-FM-VO-0728+8-30'],
            ['code' => '1635732464540728', 'name' => 'YFPL-fm-vo-0803+7-02'],
            ['code' => '1293845369352862', 'name' => 'YFPL-fg-vo-0730+8-05'],
            ['code' => '1492859842517084', 'name' => 'YFPL-fg-vo-0728+7-04'],
            ['code' => '743419362184238', 'name' => 'YFPL-fm-vo-0724+8-2'],
            ['code' => '4434188670159985', 'name' => 'YFPL-ru-vo-0803+7-02'],
            ['code' => '1442581753589174', 'name' => 'YFPL-fm-vo-0806-3-70'],
            ['code' => '1444827633985381', 'name' => 'YFPL-fm-vo-0806-3-68'],
            ['code' => '1463431345157724', 'name' => 'YFPL-fm-vo-0806-3-67'],
            ['code' => '829925992802759', 'name' => 'YFPL-fm-vo-0806+8-40'],
            ['code' => '830420029555107', 'name' => 'YFPL-fm-vo-0806+8-39'],
            ['code' => '830993563361323', 'name' => 'YFPL-fm-vo-0806+8-38'],
            ['code' => '831311452650788', 'name' => 'YFPL-fm-vo-0806+8-37'],
            ['code' => '831894832578223', 'name' => 'YFPL-fm-vo-0806+8-36'],
            ['code' => '834697292274298', 'name' => 'YFPL-fm-vo-0806+8-35'],
            ['code' => '834795292981809', 'name' => 'YFPL-fm-vo-0806+8-34'],
            ['code' => '835656822189887', 'name' => 'YFPL-fm-vo-0806+8-33'],
            ['code' => '836689385379834', 'name' => 'YFPL-fm-vo-0806+8-32'],
            ['code' => '1212604194044871', 'name' => 'YFPL-fm-vo-0806+8-20'],
            ['code' => '1218795263667432', 'name' => 'YFPL-fm-vo-0806+8-19'],
            ['code' => '1221512320169047', 'name' => 'YFPL-fm-vo-0806+8-18'],
            ['code' => '1225253092727153', 'name' => 'YFPL-fm-vo-0806+8-17'],
            ['code' => '1228701259426384', 'name' => 'YFPL-fm-vo-0806+8-16'],
            ['code' => '1235382222087314', 'name' => 'YFPL-fm-vo-0806+8-10'],
            ['code' => '1236635281786599', 'name' => 'YFPL-fm-vo-0806+8-09'],
            ['code' => '1238122581396292', 'name' => 'YFPL-fm-vo-0806+8-08'],
            ['code' => '1239094218212023', 'name' => 'YFPL-fm-vo-0806+8-07'],
            ['code' => '1245544477295857', 'name' => 'YFPL-fm-vo-0806+8-06'],
            ['code' => '1245583811077315', 'name' => 'YFPL-fm-vo-0806+8-05'],
            ['code' => '819808514023606', 'name' => 'YFPL-cs-vo-0809+8-52'],
            ['code' => '1573336697088022', 'name' => 'YFPL-cs-vo-0809+8-51'],
            ['code' => '919668287795758', 'name' => 'YFPL-FM-VO-0728+8-10'],
            ['code' => '1343520221143674', 'name' => 'YFPL-FM-VO-0728+8-07'],
            ['code' => '26152963994370486', 'name' => 'YFPL-FM-VO-0728+8-08'],
            ['code' => '844063004747520', 'name' => 'YFPL-FM-VO-0730+8-29'],
            ['code' => '1764733164507806', 'name' => 'YFPL-fm-vo-0731-3-07'],
            ['code' => '1283765323731769', 'name' => 'YFPL-fm-vo-0731-3-06'],
            ['code' => '26564899066500398', 'name' => 'YFPL-fm-vo-0731-3-05'],
            ['code' => '2125157728269358', 'name' => 'YFPL-fm-vo-0731-3-04'],
            ['code' => '1217235013649168', 'name' => 'YFPL-fm-vo-0731-3-09'],
            ['code' => '26632610169724995', 'name' => 'YFPL-fm-vo-0731-3-02'],
            ['code' => '914653624285635', 'name' => 'YFPL-fm-vo-0803+7-03'],
            ['code' => '937050745619959', 'name' => 'YFPL-cd-0701+6-01'],
            ['code' => '946093961490991', 'name' => 'YFPL-cd-0701+6-02'],
            ['code' => '1480762437134528', 'name' => 'YFPL-EA-0720+8-03'],
            ['code' => '1843665686324874', 'name' => 'YFPL-gf-vo-0724-3-05'],
            ['code' => '1646630236343068', 'name' => 'YFPL-FM-VO-0728+8-03'],
            ['code' => '1563405211910578', 'name' => 'YFPL-CS-vo-0807+7-07'],
            ['code' => '1565235377902353', 'name' => 'YFPL-CS-vo-0807+7-06'],
            ['code' => '2162794541128311', 'name' => 'YFPL-FM-VO-0728+8-29'],
            ['code' => '1332574112036549', 'name' => 'YFPL-FM-VO-0728+8-26'],
            ['code' => '757789190386525', 'name' => 'YFPL-fm-vo-0803+7-01'],
            ['code' => '1607133717267827', 'name' => 'YFPL-fm-vo-0803+7-42'],
            ['code' => '3143715485978014', 'name' => 'YFPL-fg-vo-0728+7-01'],
            ['code' => '1560506839414396', 'name' => 'YFPL-fg-vo-0730+8-01'],
            ['code' => '1714883836555872', 'name' => 'YFPL-FM-VO-0728+8-04'],
            ['code' => '1458201882603415', 'name' => 'YFPL-fg-vo-0730+7-01'],
            ['code' => '1465722075002348', 'name' => 'YFPL-fm-vo-0806-3-64'],
            ['code' => '953232684338562', 'name' => 'YFPL-FM-0716+8-01'],
            ['code' => '1853567005337864', 'name' => 'YFPL-fg-vo-0730+8-04'],
            ['code' => '914671164729137', 'name' => 'YFPL-fg-vo-0728+7-06'],
        ];

        $intro = "闲置账户提醒\n\n"
            . "为避免资源浪费，优化账户使用率，账户闲置时间大于15天，系统将自动进行回收；\n"
            . "如暂时不使用该账户，请前往账户管理页面提交回收。\n"
            . "感谢您的理解与支持！\n\n"
            . "以下为贵司【闲置3天及以上】账户清单：\n\n";

        $tableHeader = sprintf("%-3s %-17s %s\n", '序号', '账户ID', '账户名称');
        $tableHeader .= str_repeat('-', 44) . "\n";
        $table = $tableHeader;
        $messages = [];

        foreach ($accounts as $index => $account) {
            $row = sprintf(
                "%-3d %-17s %s\n",
                $index + 1,
                $account['code'],
                $account['name']
            );

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
