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

namespace App\Service;

use Goletter\Server\Service\Service;
use Google\Client;
use Google\Service\Sheets;
use Google\Service\Sheets\ValueRange;
use Hyperf\Di\Annotation\Inject;

class GoogleSheetsService extends Service
{
    #[Inject]
    protected GoogleAuthService $authService;

    protected Sheets $service;

    protected function getSheetsService(string $accessToken): Sheets
    {
        $client = new Client();
        $client->setAccessToken($accessToken);

        return new Sheets($client);
    }

    /**
     * 创建新的电子表格
     */
    public function createSpreadsheet(string $accessToken, string $title): \Google\Service\Sheets\Spreadsheet
    {
        $service = $this->getSheetsService($accessToken);

        $spreadsheet = new \Google\Service\Sheets\Spreadsheet([
            'properties' => ['title' => $title],
        ]);

        return $service->spreadsheets->create($spreadsheet);
    }

    /**
     * 读取单元格数据
     */
    public function readCells(
        string $accessToken,
        string $spreadsheetId,
        string $range = 'Sheet1!A1:Z1000'
    ): array {
        $service = $this->getSheetsService($accessToken);

        $response = $service->spreadsheets_values->get($spreadsheetId, $range);
        return $response->getValues() ?? [];
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
        $service = $this->getSheetsService($accessToken);

        $body = new ValueRange([
            'values' => $values,
        ]);

        $service->spreadsheets_values->update(
            $spreadsheetId,
            $range,
            $body,
            ['valueInputOption' => 'USER_ENTERED']
        );
    }

    /**
     * 批量写入数据
     */
    public function batchWrite(
        string $accessToken,
        string $spreadsheetId,
        array $data
    ): void {
        $service = $this->getSheetsService($accessToken);

        $batchData = [];
        foreach ($data as $item) {
            $batchData[] = new \Google\Service\Sheets\ValueRange([
                'range' => $item['range'],
                'values' => $item['values'],
            ]);
        }

        $body = new \Google\Service\Sheets\BatchUpdateValuesRequest([
            'valueInputOption' => 'USER_ENTERED',
            'data' => $batchData,
        ]);

        $service->spreadsheets_values->batchUpdate($spreadsheetId, $body);
    }

    /**
     * 添加新的工作表
     */
    public function addSheet(string $accessToken, string $spreadsheetId, string $title): void
    {
        $service = $this->getSheetsService($accessToken);

        $requests = [
            new \Google\Service\Sheets\Request([
                'addSheet' => new \Google\Service\Sheets\AddSheetRequest([
                    'properties' => ['title' => $title],
                ]),
            ]),
        ];

        $body = new \Google\Service\Sheets\BatchUpdateSpreadsheetRequest([
            'requests' => $requests,
        ]);

        $service->spreadsheets->batchUpdate($spreadsheetId, $body);
    }
}
