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

namespace App\Service\Adv\GoogleDoc;

use App\Model\AccountDemand;
use App\Service\Adv\AdvException;
use App\Service\Adv\Interface\AccountApi;
use Goletter\Docs\Contract\SheetsInterface;
use Goletter\Docs\DocsManager;
use Hyperf\Collection\Arr;
use Throwable;

/**
 * TK + Google 文档渠道：通过 Sheets 追加开户行完成「开户/授权」.
 */
class GoogleDocAccountApi extends AdvException implements AccountApi
{
    protected SheetsInterface $sheets;

    public function __construct(
        protected string $token,
        DocsManager $docs,
    ) {
        $this->sheets = $docs->platform('google')->sheets();
    }

    public function getAccount(string $accountId): array
    {
        throw new AdvException(500, 'Google文档渠道不支持获取账户详情', null, $accountId, 0, 'googledoc', 'adv');
    }

    public function updateAccountName(string $accountId, string $name): void
    {
        throw new AdvException(500, 'Google文档渠道不支持更新账户名称', null, $accountId, 0, 'googledoc', 'adv');
    }

    /**
     * 向渠道申请表追加一行，并标记 is_auth=1.
     */
    public function open(AccountDemand $accountDemand): void
    {
        $accountKey = (string) $accountDemand->code;

        if (! $this->token) {
            throw new AdvException(500, '渠道缺少申请表链接或 token', null, $accountKey, 0, 'googledoc', 'adv');
        }

        $parsed = $this->sheets->parseSpreadsheetUrl($accountDemand->sheel_url);
        $spreadsheetId = (string) Arr::get($parsed, 'spreadsheet_id', '');
        $gid = Arr::get($parsed, 'gid', 0);
        if ($spreadsheetId === '') {
            throw new AdvException(500, '无法解析 Google Sheets spreadsheet_id', null, $accountKey, 0, 'googledoc', 'adv');
        }

        $busineCode = (string) Arr::get($accountDemand, 'busine_code', '');
        $timezone = mapGmtTimezoneToEtc((string) ($accountDemand->timezone ?? ''));

        try {
            $this->sheets->appendCells(
                ['access_token' => $this->token],
                $spreadsheetId,
                "gid:{$gid}",
                [
                    null,
                    null,
                    null,
                    $accountDemand->name,
                    $timezone,
                    $accountDemand->money,
                    $accountDemand->currency,
                    $busineCode,
                    'casino game',
                    null,
                    null,
                    null,
                    true,
                ]
            );
        } catch (Throwable $e) {
            throw new AdvException(500, $e->getMessage(), $e, $accountDemand->code, 0, 'googledoc', 'adv');
        }
    }

    /**
     * 广告账户充值
     */
    public function recharge(AccountDemand $accountDemand): void
    {
        if ($accountDemand->transaction_id) {
            return;
        }

        $parsed = $this->sheets->parseSpreadsheetUrl($accountDemand->sheel_url);
        $spreadsheetId = (string) Arr::get($parsed, 'spreadsheet_id', '');
        $gid = Arr::get($parsed, 'gid', 0);

        try {
            $result = $this->sheets->appendCells(
                ['access_token' => $this->token],
                $spreadsheetId,
                "gid:{$gid}",
                [
                    null,
                    null,
                    null,
                    null,
                    $accountDemand->name,
                    null,
                    $accountDemand->money,
                    true,
                ]
            );
            $applyId = Arr::get($result, 'values.2', 0);
            $accountDemand->update(['transaction_id' => $applyId, 'status' => 2]);
        } catch (Throwable $e) {
            throw new AdvException(500, $e->getMessage(), $e, $accountDemand->code, 0, 'googledoc', 'adv');
        }
    }

    /**
     * 账户减款.
     */
    public function deduction(AccountDemand $accountDemand): void
    {
        if ($accountDemand->transaction_id) {
            return;
        }

        $parsed = $this->sheets->parseSpreadsheetUrl($accountDemand->sheel_url);
        $spreadsheetId = (string) Arr::get($parsed, 'spreadsheet_id', '');
        $gid = Arr::get($parsed, 'gid', 0);

        try {
            $result = $this->sheets->appendCells(
                ['access_token' => $this->token],
                $spreadsheetId,
                "gid:{$gid}",
                [
                    null,
                    null,
                    null,
                    null,
                    $accountDemand->name,
                    null,
                    '-' . $accountDemand->money,
                    true,
                ]
            );
            $applyId = Arr::get($result, 'values.2', 0);
            $accountDemand->update(['transaction_id' => $applyId]);
        } catch (Throwable $e) {
            throw new AdvException(500, $e->getMessage(), $e, $accountDemand->code, 0, 'googledoc', 'adv');
        }
    }

    /**
     * 账户清零.
     */
    public function reset(AccountDemand $accountDemand): void
    {
        if ($accountDemand->transaction_id) {
            return;
        }

        $parsed = $this->sheets->parseSpreadsheetUrl($accountDemand->sheel_url);
        $spreadsheetId = (string) Arr::get($parsed, 'spreadsheet_id', '');
        $gid = Arr::get($parsed, 'gid', 0);

        try {
            $result = $this->sheets->appendCells(
                ['access_token' => $this->token],
                $spreadsheetId,
                "gid:{$gid}",
                [
                    null,
                    null,
                    null,
                    $accountDemand->name,
                    null,
                    true,
                ]
            );
            $applyId = Arr::get($result, 'values.2', 0);
            $accountDemand->update(['apply_id' => $applyId]);
        } catch (Throwable $e) {
            throw new AdvException(500, $e->getMessage(), $e, $accountDemand->code, 0, 'googledoc', 'adv');
        }
    }

    /**
     * 账户回收.
     */
    public function recycle(AccountDemand $accountDemand): void
    {
        throw new AdvException(500, 'Google文档渠道不支持回收', null, (string) $accountDemand->code, 0, 'googledoc', 'adv');
    }

    /**
     * 账户绑定.
     */
    public function bind(AccountDemand $accountDemand): void
    {
        if ($accountDemand->transaction_id) {
            return;
        }

        $parsed = $this->sheets->parseSpreadsheetUrl($accountDemand->sheel_url);
        $spreadsheetId = (string) Arr::get($parsed, 'spreadsheet_id', '');
        $gid = Arr::get($parsed, 'gid', 0);

        try {
            $result = $this->sheets->appendCells(
                ['access_token' => $this->token],
                $spreadsheetId,
                "gid:{$gid}",
                [
                    null,
                    null,
                    null,
                    null,
                    $accountDemand->name,
                    $accountDemand->email,
                    null,
                    true,
                ]
            );
            $applyId = Arr::get($result, 'values.2', 0);
            $accountDemand->update(['transaction_id' => $applyId]);
        } catch (Throwable $e) {
            throw new AdvException(500, $e->getMessage(), $e, $accountDemand->code, 0, 'googledoc', 'adv');
        }
    }

    /**
     * 账户解绑.
     */
    public function unBind(AccountDemand $accountDemand): void
    {
        if ($accountDemand->transaction_id) {
            return;
        }

        $parsed = $this->sheets->parseSpreadsheetUrl($accountDemand->sheel_url);
        $spreadsheetId = (string) Arr::get($parsed, 'spreadsheet_id', '');
        $gid = Arr::get($parsed, 'gid', 0);

        try {
            $result = $this->sheets->appendCells(
                ['access_token' => $this->token],
                $spreadsheetId,
                "gid:{$gid}",
                [
                    null,
                    null,
                    null,
                    null,
                    $accountDemand->name,
                    null,
                    $accountDemand->email,
                    true,
                ]
            );
            $applyId = Arr::get($result, 'values.2', 0);
            $accountDemand->update(['transaction_id' => $applyId]);
        } catch (Throwable $e) {
            throw new AdvException(500, $e->getMessage(), $e, $accountDemand->code, 0, 'googledoc', 'adv');
        }
    }

    public function pauseAdCampaign(AccountDemand $accountDemand): void
    {
        throw new AdvException(500, 'Google文档渠道不支持暂停广告系列', null, (string) $accountDemand->account_id, 0, 'googledoc', 'adv');
    }

    public function updateAdCampaignStatus($accountDemand, $status): void
    {
        throw new AdvException(500, 'Google文档渠道不支持修改广告系列状态', null, $accountDemand->code, 0, 'googledoc', 'adv');
    }

    public function addAssignedUser($accountDemand)
    {
        // Google 文档渠道无 open() 写表完成申请，无平台侧授权接口
    }

    public function removeAssignedUser($accountDemand)
    {
    }
}
