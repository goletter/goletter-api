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

use Goletter\Docs\DocsManager;
use Goletter\Docs\Platform\GooglePlatform;
use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Di\Annotation\Inject;

#[Command]
class TestCommand extends HyperfCommand
{
    #[Inject]
    protected DocsManager $docs;

    public function __construct()
    {
        parent::__construct('test:to');
    }

    public function configure(): void
    {
        parent::configure();
        $this->setDescription('测试 Google Sheets 读写（支持 gid）');
    }

    public function handle()
    {
        $accessToken = '';
        $token = ['access_token' => $accessToken];

        /** @var GooglePlatform $platform */
        $platform = $this->docs->platform('google');
        $sheets = $platform->sheets();

        $spreadsheetId = '1f6KLBbJnsOMKMKUxkwH-Bfwm4YWkFA_RBibJbbLIq8M';
        $gid = 649506568;

        // 按 F 列内容查找指定行（只返回匹配且有数据的行）
        // $rows = $sheets->findRows($token, $spreadsheetId, "gid:{$gid}", 'F', '333333333');

        // 读整表（自动过滤空行、裁掉行尾空单元格）
        // $all = $sheets->readCells($token, $spreadsheetId, "gid:{$gid}");
    }
}
