<?php

declare(strict_types=1);

namespace App\Service;

use Goletter\Docs\Google\GoogleSheets;
use Google\Service\Drive\Permission;
use Google\Service\Sheets\Spreadsheet;
use Hyperf\Di\Annotation\Inject;

class GoogleSheetsService
{
    #[Inject]
    protected GoogleSheets $sheets;

    /**
     * 创建新的电子表格
     */
    public function createSpreadsheet(string $accessToken, string $title): Spreadsheet
    {
        return $this->sheets->createSpreadsheet($accessToken, $title);
    }

    /**
     * 读取单元格数据
     */
    public function readCells(
        string $accessToken,
        string $spreadsheetId,
        string $range = 'Sheet1!A1:Z1000'
    ): array {
        return $this->sheets->readCells($accessToken, $spreadsheetId, $range);
    }

    /**
     * 写入数据到单元格
     */
    public function writeCells(
        string $accessToken,
        string $spreadsheetId,
        string $range,
        array $values
    ): void {
        $this->sheets->writeCells($accessToken, $spreadsheetId, $range, $values);
    }

    /**
     * 将表格设置为知道链接的人可读。
     */
    public function shareSpreadsheetForAnyoneReader(string $accessToken, string $spreadsheetId): Permission
    {
        return $this->sheets->shareSpreadsheetForAnyoneReader($accessToken, $spreadsheetId);
    }

    /**
     * 将表格移动到“根目录 / 日期”结构中。
     */
    public function moveSpreadsheetToDateFolder(
        string $accessToken,
        string $spreadsheetId,
        ?string $rootFolderName = null,
        ?string $date = null
    ): array {
        return $this->sheets->moveSpreadsheetToDateFolder($accessToken, $spreadsheetId, $rootFolderName, $date);
    }

    /**
     * 批量写入数据
     */
    public function batchWrite(
        string $accessToken,
        string $spreadsheetId,
        array $data
    ): void {
        $this->sheets->batchWrite($accessToken, $spreadsheetId, $data);
    }

    /**
     * 添加新的工作表
     */
    public function addSheet(string $accessToken, string $spreadsheetId, string $title, ?int $sheetId = null): void
    {
        $this->sheets->addSheet($accessToken, $spreadsheetId, $title, $sheetId);
    }
}
