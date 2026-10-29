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

namespace App\Amqp\Consumer;

use App\Amqp\Queue\AccountRechargeQueue;
use App\Amqp\Sharded\ShardedConsumer;
use Goletter\Docs\DocsManager;
use Hyperf\Amqp\Result;
use Hyperf\Collection\Arr;
use Hyperf\Di\Annotation\Inject;
use PhpAmqpLib\Message\AMQPMessage;
use RuntimeException;

use function Goletter\Utils\logging;

class AccountRechargeConsumer extends ShardedConsumer
{
    #[Inject]
    protected DocsManager $docs;

    protected function handle(mixed $data, AMQPMessage $message): Result
    {
        $payload = is_array($data) ? $data : [];
        $name = Arr::get($payload, 'name');
        $money = Arr::get($payload, 'money');
        $sheetUrl = Arr::get($payload, 'sheet_url');

        $sheets = $this->docs->platform('google')->sheets();
        $parsed = $sheets->parseSpreadsheetUrl($sheetUrl);
        $spreadsheetId = (string) Arr::get($parsed, 'spreadsheet_id', '');
        $gid = Arr::get($parsed, 'gid', 0);
        if ($spreadsheetId === '') {
            throw new RuntimeException('无法解析 Google Spreadsheet URL');
        }

        $token = [
            'access_token' => (string) Arr::get($payload, 'access_token', ''),
        ];

        $sheets->appendCells(
            $token,
            $spreadsheetId,
            'gid:' . $gid,
            $this->buildSheetRow($name, $money)
        );

        logging([
            'event' => 'appended',
            'shard' => $this->getShard(),
            'name' => $name,
            'money' => $money,
            'spreadsheet_id' => $spreadsheetId,
            'gid' => $gid,
        ], '账户充值已写入表格', 'queue');

        return Result::ACK;
    }

    protected static function queueClass(): string
    {
        return AccountRechargeQueue::class;
    }

    /**
     * 表格列位：E=name，G=money，H=标记；其余留空.
     *
     * @return list<null|bool|float|int|string>
     */
    private function buildSheetRow(mixed $name, mixed $money): array
    {
        return [
            null,
            null,
            null,
            null,
            $name,
            null,
            $money,
            true,
        ];
    }
}
