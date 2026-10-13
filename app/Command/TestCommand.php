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
        $this->setDescription('测试腾讯文档表格追加');
    }

    public function handle()
    {
        $accessToken = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJjbHQiOiIxZjY4Y2E2ZDRmYzg0N2UxYTI3NjRiY2JjNDEzZDgzNyIsInR5cCI6MSwiZXhwIjoxNzk0NTA2NTgxLjMyMDEwNiwiaWF0IjoxNzkxOTE0NTgxLjMyMDEwNiwic3ViIjoiZTJkZjI3ZGExZWMzNGNlYjljMzUzZGViYmVlZDZhZGMifQ.HN-SD73P7CEnfbMeL1aGDavISMgaB25KlAKF28ShHyM';
        $openId = 'e2df27da1ec34ceb9c353debbeed6adc';

        $sheets = $this->docs->sheets('tencent');
        $parsed = $sheets->parseSpreadsheetUrl('https://docs.qq.com/sheet/DSEhEVGJuQ1dFQXhM?tab=BB08J2');
        $this->line('parsed: ' . json_encode($parsed, JSON_UNESCAPED_UNICODE));

        $token = [
            'access_token' => $accessToken,
            'open_id' => $openId,
        ];

        $result = $sheets->appendCells($token, $parsed['spreadsheet_id'], 'A1', [
            ['张三1', '技术部1'],
            ['张三2', '技术部2'],
            ['张三3', '技术部3'],
            ['张三4', '技术部4'],
            ['张三5', '技术部5'],
            ['张三6', '技术部6'],
            ['张三7', '技术部7'],
            ['张三8', '技术部8'],
        ]);

        $this->info('appendCells ok');
        $this->line(json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }
}
