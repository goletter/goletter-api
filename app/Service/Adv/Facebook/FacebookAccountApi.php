<?php

namespace App\Service\Adv\Facebook;

use App\Constants\AccountStatusConstant;
use App\Constants\CheckStatusConstant;
use App\Constants\DemandTypeConstant;
use App\Constants\ExchangeRateConstant;
use App\Constants\LogTypeConstant;
use App\Constants\StatusConstant;
use App\Event\AccountDemandSuccessEvent;
use App\Job\AccountAddAssignedUserJob;
use App\Job\CardQuotaDecreaseJob;
use App\Job\CardQuotaJob;
use App\Model\Account;
use App\Model\AccountDemand;
use App\Model\AccountUser;
use App\Model\Busines;
use App\Model\Card;
use App\Model\Dict;
use App\Service\Adv\AdvException;
use App\Service\Adv\Interface\AccountApi;
use App\Service\Card\CardResolver;
use App\Service\QueueService;
use Goletter\Adv\Platforms\Facebook\FacebookAccount;
use Goletter\Adv\Platforms\Facebook\FacebookCampaign;
use Goletter\Adv\Platforms\Facebook\FacebookClient;
use Hyperf\Collection\Arr;
use Hyperf\Di\Annotation\Inject;
use function Goletter\Utils\event;
use function Hyperf\Config\config;
use function Hyperf\Support\env;

class FacebookAccountApi extends AdvException implements AccountApi
{
    protected FacebookAccount $facebookAccount;

    protected FacebookCampaign $facebookCampaign;

    #[Inject]
    private QueueService $queueService;

    public function __construct(FacebookClient $client)
    {
        $this->facebookAccount = new FacebookAccount($client);
        $this->facebookCampaign = new FacebookCampaign($client);
    }

    /**
     * 获取广告账户详情
     * @param string $accountId
     * @return array|null
     */
    public function getAccount(string $accountId): array
    {
        return [];
    }

    /**
     * 更新广告账户名称
     * @param string $accountId
     * @param string $name
     * @return void
     */
    public function updateAccountName(string $accountId, string $name): void
    {
        //
    }

    /**
     * 开户
     * @param $updateCurrency 不更新货币
     * @param $updateBusinessInfo 不更新公司信息
     * @return void
     */
    public function open($account, bool $updateCurrency = false, bool $updateBusinessInfo = false): void
    {
        //
    }


    /**
     * 更新facebook的时区
     * @param $timezone 字符串时区 GMT +7:00
     * @return void
     */
    public function updateAccountFacebookTimezone($account,string $timezone): void
    {
        //
    }

    /**
     * 账户充值
     * @return array
     */
    public function recharge($accountDemand): void
    {
        //
    }

    /**
     * 账户减款
     * @return array
     */
    public function deduction($accountDemand): void
    {
        //
    }

    /**
     * 广告账户清零
     * @param string $accountId
     * @param int|null $spendCap
     * @return array
     */
    public function reset($accountDemand): void
    {
        //
    }

    /**
     * 回收
     * @param $accountDemand
     * @return array
     * */
    public function recycle($accountDemand): void
    {
        //
    }

    /**
     * 广告账户绑定
     * @return void
     */
    public function bind($accountDemand): void
    {
        //
    }

    /**
     * 广告账户解绑
     * @return void
     */
    public function unBind($accountDemand): void
    {
        //
    }


    /**
     * 暂停广告系列
     * @param $accountId
     * @return array
     * */
    public function pauseAdCampaign($accountDemand): void
    {
        //
    }

    /**
     * 修改广告系列状态
     * @param $account
     * @param string $status  [DISABLE="暂停", ENABLE="开启",DELETE="删除"]
     * @return array
     */
    public function updateAdCampaignStatus($account, $status): void
    {
        //
    }

    /**
     * 修改广告系列状态
     */
    public function getlistActivities($parameters)
    {
        //
    }

    /**
     * 授权
     * @param $account
     * @return void
     */
    public function addAssignedUser($account,$role='ADMIN')
    {
        //
    }

    /**
     * 解除授权
     * DELETE /act_{ad_account_id}/assigned_users
     * @param $account
     * @return void
     */
    public function removeAssignedUser($account)
    {
        //
    }

    /**
     * 解析要授权/解绑的个号 user_id：gehao_user_id > 系统用户 > BM user_id
     */
    protected function resolveAssignedUserId($account): string
    {
        return '';
    }
}