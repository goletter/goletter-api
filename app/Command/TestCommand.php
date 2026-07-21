<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\GoogleSheetsService;
use Goletter\Docs\Google\Exceptions\GoogleApiException;
use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Di\Annotation\Inject;
use function Hyperf\Support\env;

#[Command]
class TestCommand extends HyperfCommand
{
    private const SPREADSHEET_TITLE = '我的数据报表';

    private const DEFAULT_SHEET = 'Sheet1';

    #[Inject]
    protected GoogleSheetsService $sheetsService;

    public function __construct()
    {
        parent::__construct('test:to');
    }

    public function configure(): void
    {
        parent::configure();
        $this->setDescription('测试 Google Sheets 创建、写入、归档与分享');
    }

    public function handle(): int
    {
        $accessToken = (string) env('GOOGLE_TOKEN', '');
        if ($accessToken === '') {
            $this->error('缺少环境变量 GOOGLE_TOKEN');

            return self::FAILURE;
        }

        try {
            $sheetGids = $this->sheetGids();
            $sheets = $this->sampleSheets($sheetGids);

            $spreadsheet = $this->sheetsService->createSpreadsheet($accessToken, self::SPREADSHEET_TITLE);
            $spreadsheetId = $spreadsheet->getSpreadsheetId();
            if (! is_string($spreadsheetId) || $spreadsheetId === '') {
                $this->error('创建电子表格失败：响应中缺少 spreadsheetId');
                $this->line($this->toJson($spreadsheet->toSimpleObject()));

                return self::FAILURE;
            }

            $this->ensureSheets($accessToken, $spreadsheetId, $sheetGids);
            $this->sheetsService->batchWrite($accessToken, $spreadsheetId, $this->buildBatchData($sheets));

            $folder = $this->sheetsService->moveSpreadsheetToDateFolder(
                $accessToken,
                $spreadsheetId,
                env('GOOGLE_DRIVE_ROOT_FOLDER_NAME', 'Goletter')
            );
            $permission = $this->sheetsService->shareSpreadsheetForAnyoneReader($accessToken, $spreadsheetId);
            $spreadsheetUrl = $spreadsheet->getSpreadsheetUrl()
                ?: "https://docs.google.com/spreadsheets/d/{$spreadsheetId}/edit";

            $this->info('Google Sheets 测试完成');
            $this->line($this->toJson([
                'spreadsheet_id' => $spreadsheetId,
                'sheet_titles' => array_keys($sheets),
                'sheet_gids' => $sheetGids,
                'folder' => $folder,
                'spreadsheet_url' => $spreadsheetUrl,
                'share_url' => "{$spreadsheetUrl}?usp=sharing",
                'permission' => 'anyone_with_link_reader',
                'share_permission' => $permission->toSimpleObject(),
            ]));

            return self::SUCCESS;
        } catch (GoogleApiException $e) {
            $this->error('Google API 调用失败');
            $this->line($this->toJson([
                'code' => $e->getCode(),
                'message' => $e->getMessage(),
                'response' => $e->getResponse(),
            ]));

            return self::FAILURE;
        }
    }

    /**
     * @return array<string, int>
     */
    private function sheetGids(): array
    {
        return [
            self::DEFAULT_SHEET => 0,
            'Sheet2' => 1001,
            'Sheet3' => 1002,
        ];
    }

    /**
     * @param array<string, int> $sheetGids
     * @return array<string, list<list<string>>>
     */
    private function sampleSheets(array $sheetGids): array
    {
        $header = ['姓名', '部门', '职位', '入职日期'];

        return [
            self::DEFAULT_SHEET => [
                $header,
                ['张三', '技术部', '高级工程师', $this->sheetHyperlink($sheetGids['Sheet2'], '2024-01-15')],
                ['李四', '产品部', $this->sheetHyperlink($sheetGids['Sheet3'], '产品经理'), '2024-02-20'],
                ['王五', '设计部', 'UI设计师', '2024-03-10'],
            ],
            'Sheet2' => [
                $header,
                ['赵六', '运营部', '运营专员', '2024-04-01'],
                ['钱七', '市场部', '市场经理', '2024-04-18'],
            ],
            'Sheet3' => [
                $header,
                ['孙八', '客服部', '客服主管', '2024-05-06'],
                ['周九', '财务部', '会计', '2024-05-22'],
            ],
        ];
    }

    /**
     * @param array<string, int> $sheetGids
     */
    private function ensureSheets(string $accessToken, string $spreadsheetId, array $sheetGids): void
    {
        foreach ($sheetGids as $title => $gid) {
            if ($title === self::DEFAULT_SHEET) {
                continue;
            }

            $this->sheetsService->addSheet($accessToken, $spreadsheetId, $title, $gid);
        }
    }

    /**
     * @param array<string, list<list<string>>> $sheets
     * @return list<array{range: string, values: list<list<string>>}>
     */
    private function buildBatchData(array $sheets): array
    {
        $batchData = [];
        foreach ($sheets as $title => $values) {
            $columns = max(array_map('count', $values));
            $rows = count($values);
            $batchData[] = [
                'range' => sprintf('%s!A1:%s%d', $title, $this->columnName($columns), $rows),
                'values' => $values,
            ];
        }

        return $batchData;
    }

    private function sheetHyperlink(int $gid, string $label): string
    {
        return sprintf('=HYPERLINK("#gid=%d&range=A1", "%s")', $gid, $label);
    }

    private function columnName(int $columnNumber): string
    {
        $columnName = '';
        while ($columnNumber > 0) {
            $remainder = ($columnNumber - 1) % 26;
            $columnName = chr(65 + $remainder) . $columnName;
            $columnNumber = intdiv($columnNumber - 1, 26);
        }

        return $columnName;
    }

    private function toJson(mixed $data): string
    {
        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}';
    }
}
