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
use Google\Service\Drive;
use Google\Service\Drive\DriveFile;
use Google\Service\Drive\Permission;
use Google\Service\Sheets;
use Google\Service\Sheets\ValueRange;
use GuzzleHttp\Client as GuzzleClient;

class GoogleSheetsService extends Service
{
    private const DEFAULT_SHEET_TITLE = 'Sheet1';
    private const DEFAULT_ROOT_FOLDER = 'Goletter';
    private const DRIVE_FOLDER_MIME_TYPE = 'application/vnd.google-apps.folder';
    private const VALUE_INPUT_OPTION = 'USER_ENTERED';

    protected function getSheetsService(string $accessToken): Sheets
    {
        return new Sheets($this->getGoogleClient($accessToken));
    }

    protected function getDriveService(string $accessToken): Drive
    {
        return new Drive($this->getGoogleClient($accessToken));
    }

    protected function getGoogleClient(string $accessToken): Client
    {
        $client = new Client();
        $client->setHttpClient(new GuzzleClient([
            'headers' => [
                'Accept-Encoding' => 'identity',
            ],
        ]));
        $client->setAccessToken($accessToken);

        return $client;
    }

    /**
     * 创建新的电子表格
     */
    public function createSpreadsheet(string $accessToken, string $title): \Google\Service\Sheets\Spreadsheet
    {
        return $this->getSheetsService($accessToken)
            ->spreadsheets
            ->create($this->buildSpreadsheet($title));
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
            ['valueInputOption' => self::VALUE_INPUT_OPTION]
        );
    }

    /**
     * 将表格设置为知道链接的人可读。
     */
    public function shareSpreadsheetForAnyoneReader(string $accessToken, string $spreadsheetId): Permission
    {
        $service = $this->getDriveService($accessToken);

        $permission = new Permission([
            'type' => 'anyone',
            'role' => 'reader',
            'allowFileDiscovery' => false,
        ]);

        return $service->permissions->create($spreadsheetId, $permission, [
            'sendNotificationEmail' => false,
            'supportsAllDrives' => true,
        ]);
    }

    /**
     * 将表格移动到“根目录 / 日期”结构中。
     */
    public function moveSpreadsheetToDateFolder(
        string $accessToken,
        string $spreadsheetId,
        ?string $rootFolderName = null,
        ?string $date = null
    ): array
    {
        $service = $this->getDriveService($accessToken);
        $rootName = $rootFolderName ?: self::DEFAULT_ROOT_FOLDER;
        $dateName = $date ?? date('Y-m-d');
        $rootFolder = $this->getOrCreateFolder($service, $rootName);
        $dateFolder = $this->getOrCreateFolder($service, $dateName, $rootFolder->getId());
        $dateFolderId = $dateFolder->getId();

        $this->moveFileToFolder($service, $spreadsheetId, $dateFolderId);

        return $this->formatArchiveFolder($rootName, $rootFolder->getId(), $dateName, $dateFolderId);
    }

    private function getOrCreateFolder(Drive $service, string $folderName, ?string $parentId = null): DriveFile
    {
        $folder = $this->findFolder($service, $folderName, $parentId);
        if ($folder instanceof DriveFile) {
            return $folder;
        }

        $folderData = [
            'name' => $folderName,
            'mimeType' => self::DRIVE_FOLDER_MIME_TYPE,
        ];
        if (is_string($parentId) && $parentId !== '') {
            $folderData['parents'] = [$parentId];
        }

        return $service->files->create(new DriveFile($folderData), [
            'fields' => 'id, name',
            'supportsAllDrives' => true,
        ]);
    }

    private function findFolder(Drive $service, string $folderName, ?string $parentId = null): ?DriveFile
    {
        $query = sprintf(
            "mimeType='%s' and name='%s' and trashed=false",
            self::DRIVE_FOLDER_MIME_TYPE,
            $this->escapeDriveQueryValue($folderName)
        );
        if (is_string($parentId) && $parentId !== '') {
            $query .= sprintf(" and '%s' in parents", $this->escapeDriveQueryValue($parentId));
        }

        $response = $service->files->listFiles([
            'q' => $query,
            'fields' => 'files(id, name)',
            'pageSize' => 1,
            'supportsAllDrives' => true,
        ]);

        $folders = $response->getFiles() ?? [];

        return $folders[0] ?? null;
    }

    private function escapeDriveQueryValue(string $value): string
    {
        return str_replace(['\\', "'"], ['\\\\', "\\'"], $value);
    }

    private function buildSpreadsheet(string $title): \Google\Service\Sheets\Spreadsheet
    {
        return new \Google\Service\Sheets\Spreadsheet([
            'properties' => ['title' => $title],
            'sheets' => [
                [
                    'properties' => [
                        'title' => self::DEFAULT_SHEET_TITLE,
                    ],
                ],
            ],
        ]);
    }

    private function moveFileToFolder(Drive $service, string $fileId, string $folderId): void
    {
        $file = $service->files->get($fileId, [
            'fields' => 'parents',
            'supportsAllDrives' => true,
        ]);

        $service->files->update($fileId, new DriveFile(), [
            'addParents' => $folderId,
            'removeParents' => implode(',', $file->parents ?? []),
            'fields' => 'id, parents',
            'supportsAllDrives' => true,
        ]);
    }

    private function formatArchiveFolder(
        string $rootName,
        string $rootFolderId,
        string $dateName,
        string $dateFolderId
    ): array {
        return [
            'path' => "{$rootName}/{$dateName}",
            'root_folder' => $this->formatFolder($rootName, $rootFolderId),
            'date_folder' => $this->formatFolder($dateName, $dateFolderId),
        ];
    }

    private function formatFolder(string $name, string $id): array
    {
        return [
            'folder_id' => $id,
            'folder_name' => $name,
            'folder_url' => "https://drive.google.com/drive/folders/{$id}",
        ];
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
            'valueInputOption' => self::VALUE_INPUT_OPTION,
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
