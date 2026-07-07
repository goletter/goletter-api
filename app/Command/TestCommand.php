<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\GoogleSheetsService;
use Goletter\Server\Service\QueueService;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Command\Annotation\Command;
use Hyperf\Di\Annotation\Inject;
use Psr\Container\ContainerInterface;

#[Command]
class TestCommand extends HyperfCommand
{
    #[Inject]
    private QueueService $queueService;

    #[Inject]
    protected GoogleSheetsService $sheetsService;

    public function __construct(protected ContainerInterface $container)
    {
        parent::__construct('test:to');
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('测试');
    }

    public function handle()
    {
        try {
            $accessToken = '';

            // 1. 创建电子表格
            $spreadsheet = $this->sheetsService->createSpreadsheet($accessToken, '我的数据报表');
            $sheetId = $spreadsheet->getSpreadsheetId();

            // 2. 写入表头
            $this->sheetsService->writeCells($accessToken, $sheetId, 'Sheet1!A1:D1', [
                ['姓名', '部门', '职位', '入职日期'],
            ]);

            // 3. 写入数据
            $this->sheetsService->writeCells($accessToken, $sheetId, 'Sheet1!A2:D4', [
                ['张三', '技术部', '高级工程师', '2024-01-15'],
                ['李四', '产品部', '产品经理', '2024-02-20'],
                ['王五', '设计部', 'UI设计师', '2024-03-10'],
            ]);

            $data = [
                'spreadsheet_id' => $sheetId,
                'spreadsheet_url' => "https://docs.google.com/spreadsheets/d/{$sheetId}/edit",
            ];
            dd($data);
        } catch (\Google\Service\Exception $e) {
            // 打印完整的错误详情
            var_dump($e->getMessage());
            var_dump($e->getErrors()); // 这里会包含详细的错误原因
        }

    }
}
