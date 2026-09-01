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
        $this->setDescription('测试');
    }

    public function handle()
    {
        /*$accessToken = '';
        $openId = 'e2df27da1ec34ceb9c353debbeed6adc';
        $spreadsheetId = 'DSEJrdWtXV0ZsRnVV';

        $token = [
            'access_token' => $accessToken,
            'open_id' => $openId, // 或 user_id
        ];

        $values = $this->docs->sheets('tencent')->readCells(
            $token,
            $spreadsheetId,
        );
        dd($values);*/
    }
}
