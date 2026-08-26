<?php

namespace App\Service\Adv\Interface;

use App\Model\AccountDemand;

interface AccountApi
{
    /**
     * 获取账户详情
     */
    public function getAccount(string $accountId): array;

    /**
     * 更新账户名称
     */
    public function updateAccountName(string $accountId, string $name): void;

    /**
     * 账户开户
     */
    public function open($account,bool $updateCurrency = false, bool $updateBusinessInfo = false): void;

    /**
     * 账户充值
     */
    public function recharge(AccountDemand $accountDemand):void;

    /**
     * 账户减款
     */
    public function deduction(AccountDemand $accountDemand):void;

    /**
     * 账户清零
     */
    public function reset(AccountDemand $accountDemand):void;

    /**
     * 账户回收
     * */
    public function recycle(AccountDemand $accountDemand):void;

    /**
     * 账户绑定
     */
    public function bind(AccountDemand $accountDemand):void;

    /**
     * 账户解绑
     */
    public function unBind(AccountDemand $accountDemand):void;

    /**
     * 暂停广告系列
     * */
    public function pauseAdCampaign(AccountDemand $accountDemand):void;

    /**
     * 修改广告系列状态
     */
    public function updateAdCampaignStatus($account, $status):void;

    /**
     * 授权
     */
    public function addAssignedUser($account);

    /**
     * 解除授权（DELETE /act_{ad_account_id}/assigned_users）
     */
    public function removeAssignedUser($account);
}