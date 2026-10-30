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

namespace App\Service\Adv\Interface;

use App\Model\AccountDemand;

interface AccountApi
{
    /**
     * 获取账户详情.
     */
    public function getAccount(string $accountId): array;

    /**
     * 更新账户名称.
     */
    public function updateAccountName(string $accountId, string $name): void;

    /**
     * 账户开户.
     * @param mixed $account
     */
    public function open(AccountDemand $accountDemand): void;

    /**
     * 账户充值
     */
    public function recharge(AccountDemand $accountDemand): void;

    /**
     * 账户减款.
     */
    public function deduction(AccountDemand $accountDemand): void;

    /**
     * 账户清零.
     */
    public function reset(AccountDemand $accountDemand): void;

    /**
     * 账户回收.
     * */
    public function recycle(AccountDemand $accountDemand): void;

    /**
     * 账户绑定.
     */
    public function bind(AccountDemand $accountDemand): void;

    /**
     * 账户解绑.
     */
    public function unBind(AccountDemand $accountDemand): void;

    /**
     * 暂停广告系列.
     * */
    public function pauseAdCampaign(AccountDemand $accountDemand): void;

    /**
     * 修改广告系列状态
     * @param mixed $accountDemand
     * @param mixed $status
     */
    public function updateAdCampaignStatus(AccountDemand $accountDemand, $status): void;

    /**
     * 授权.
     * @param mixed $accountDemand
     */
    public function addAssignedUser(AccountDemand $accountDemand);

    /**
     * 解除授权（DELETE /act_{ad_account_id}/assigned_users）.
     * @param mixed $accountDemand
     */
    public function removeAssignedUser(AccountDemand $accountDemand);
}
