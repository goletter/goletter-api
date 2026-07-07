<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\GoogleSheetsService;
use Goletter\Server\Service\QueueService;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Command\Annotation\Command;
use Hyperf\Di\Annotation\Inject;
use Psr\Container\ContainerInterface;
use function Hyperf\Support\env;

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
            $accessToken = env('GOOGLE_TOKEN');

            // 1. 创建电子表格
            $spreadsheet = $this->sheetsService->createSpreadsheet($accessToken, '我的数据报表');
            $sheetId = $spreadsheet->getSpreadsheetId();
            if (! is_string($sheetId) || $sheetId === '') {
                $this->output->writeln(json_encode([
                    'error' => 'Google Sheets created response did not include spreadsheetId.',
                    'spreadsheet' => $spreadsheet->toSimpleObject(),
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

                return self::FAILURE;
            }

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

            // 4. 按日期归档到 Drive 文件夹
            $folder = $this->sheetsService->moveSpreadsheetToDateFolder(
                $accessToken,
                $sheetId,
                env('GOOGLE_DRIVE_ROOT_FOLDER_NAME', 'Goletter')
            );

            // 5. 设置为知道链接的人可读
            $permission = $this->sheetsService->shareSpreadsheetForAnyoneReader($accessToken, $sheetId);
            $spreadsheetUrl = $spreadsheet->getSpreadsheetUrl() ?: "https://docs.google.com/spreadsheets/d/{$sheetId}/edit";

            $data = [
                'spreadsheet_id' => $sheetId,
                'folder' => $folder,
                'spreadsheet_url' => $spreadsheetUrl,
                'share_url' => "https://docs.google.com/spreadsheets/d/{$sheetId}/edit?usp=sharing",
                'permission' => 'anyone_with_link_reader',
                'share_permission' => $permission->toSimpleObject(),
            ];
            dd($data);
        } catch (\Google\Service\Exception $e) {
            $this->output->writeln(json_encode(
                $this->formatGoogleException($e),
                JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            ));

            return self::FAILURE;
        }

    }

    private function formatGoogleException(\Google\Service\Exception $e): array
    {
        $message = $this->decodeGoogleErrorMessage($e->getMessage());
        $decodedMessage = json_decode($message, true);

        return [
            'code' => $e->getCode(),
            'message' => json_last_error() === JSON_ERROR_NONE ? $decodedMessage : $message,
            'errors' => $e->getErrors(),
        ];
    }

    private function decodeGoogleErrorMessage(string $message): string
    {
        if (! str_starts_with($message, "\x1f\x8b")) {
            return $message;
        }

        $decoded = gzdecode($message);

        return $decoded === false ? $message : $decoded;
    }
}
