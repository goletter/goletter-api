<?php

namespace App\Service\Adv\TikTok;

use App\Model\Account;
use App\Service\Adv\AdvException;
use App\Service\Adv\Interface\AccountApi;
use Goletter\Adv\Platforms\TikTok\TikTokAccount;
use Goletter\Adv\Platforms\TikTok\TikTokBusiness;
use Goletter\Adv\Platforms\TikTok\TikTokClient;
use Hyperf\Collection\Arr;

class TikTokAccountApi extends AdvException implements AccountApi
{
    protected TikTokAccount $tikTokAccount;

    protected TikTokBusiness $tikTokBusiness;

    public function __construct(TikTokClient $client)
    {
        $this->tikTokAccount = new TikTokAccount($client);
        $this->tikTokBusiness = new TikTokBusiness($client);
    }

    /**
     * 获取广告账户详情
     * @param string $accountId
     * @return array|null
     */
    public function getAccount(string $accountId): array
    {
        try {
            $item = $this->tikTokAccount->getAdvertiser($accountId);
            $timezone = Arr::get($item, 'timezone', '');
            $currency = Arr::get($item, 'currency', '');
            $name = Arr::get($item, 'name', '');
            $status = Arr::get($item, 'status', '');
            $balance = Arr::get($item, 'balance', 0);
            $spendBalance = ($balance > 0) ? $balance * 100 : 0;
            $accountStatus = ($status == 'STATUS_ENABLE') ? 1 : 2;

            return [
                'account_status' => $accountStatus,
                'amount_spent' => Arr::get($item, 'amount_spent', 0),
                'spend_cap' => Arr::get($item, 'spend_cap', 0),
                'balance' => 0,
                'spend_balance' => $spendBalance,
                'timezone' => getTimeZone($timezone),
                'currency' => $currency,
                'name' => $name,
            ];
        } catch (\Exception $e) {
            throw new AdvException(500, $e->getMessage(), $e, $accountId, 0, 'tiktok', 'adv');
        }
    }

    /**
     * 更新广告账户名称
     * @param string $accountId
     * @param string $name
     * @return void
     */
    public function updateAccountName(string $accountId, string $name): void
    {
        try {
            $this->tikTokAccount->updateAccountName($accountId, $name);
        } catch (\Exception $e) {
            throw new AdvException(500, $e->getMessage(), $e, $accountId, 0, 'tiktok', 'adv');
        }
    }

    /**
     * 账户开户
     * @return void
     */
    public function open($accountDemand): void
    {
        try {
            $this->tikTokBusiness->assignBcAsset([
                'bc_id' => $accountDemand->email,
                'user_id' => $accountDemand->user_id,
                'asset_type' => 'ADVERTISER',
                'asset_id' => (string) $accountDemand->code,
                'advertiser_role' => 'ADMIN',
                'catalog_role' => 'ADMIN',
                'form_library_role' => 'ADMIN',
                'tt_account_roles' => ['POST'],
                'business_account_roles' => ['BUSINESS_ACCOUNT_ADMIN'],
                'store_role' => 'AD_PROMOTION'
            ]);
            $accountDemand->update(['status' => 2]);
        } catch (\Exception $e) {
            throw new AdvException(500, $e->getMessage(), $e, $accountDemand->code, 0, 'tiktok', LogTypeConstant::Account);
        }
    }

    /**
     * 账户充值
     * @return void
     */
    public function recharge($accountDemand): void
    {
        //
    }

    /**
     * 账户扣款
     * @return void
     */
    public function deduction($accountDemand): void
    {
        //
    }

    /**
     * 账户清零
     * @return void
     */
    public function reset($accountDemand): void
    {
        // TODO: Implement reset() method.
    }

    /**
     * 账户回收
     * @return void
     */
    public function recycle($accountDemand): void
    {
        //
    }

    /**
     * 账户绑定
     * @return void
     */
    public function bind($accountDemand): void
    {
        //
    }

    /**
     * 账户解绑
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
        $accountId = $accountDemand->code;
        //查询-删除广告系列
        $result = $this->tikTokAccount->getCampaign($accountId);
        if(isset($result['data']['list'])){
            $campaignIds = array_column($result['data']['list'],'campaign_id');
            $operationStatus = 'DISABLE';//暂停
            // $operationStatus = 'ENABLE';//开启
            if(!empty($campaignIds)){
                $param = [
                    'advertiser_id'=>$accountId,
                    'campaign_ids'=>$campaignIds,
                    'operation_status'=>$operationStatus,
                ];
                $result = $this->tikTokAccount->updateCampaignStatus($param);
                if($result['code'] !== 0) {
                    $accountDemand->update(['status' => 3, 'remark' => '暂停广告系列错误']);
                }
            }
        }
    }

    /**
     * 修改广告系列状态
     * @param $accountDemand
     * @param string $status  [DISABLE="暂停", ENABLE="开启",DELETE="删除"]
     * @return array
     */
    public function updateAdCampaignStatus($accountDemand, $status): void
    {
        $accountId = $accountDemand->code;
        $result = $this->tikTokAccount->getCampaign($accountId);
        if(isset($result['data']['list'])){
            $campaignIds = array_column($result['data']['list'],'campaign_id');
            $operationStatus = $status;//暂停
            if(!empty($campaignIds)){
                $param = [
                    'advertiser_id'=>$accountId,
                    'campaign_ids'=>$campaignIds,
                    'operation_status'=>$operationStatus,
                ];
                $result = $this->tikTokAccount->updateCampaignStatus($param);
                // if($result['code'] !== 0) {
                //     $accountDemand->update(['status' => StatusConstant::StatusFour, 'remark' => '暂停广告系列错误']);
                // }
            }
        }
    }

    /**
     * 授权
     * @param $account
     * @return void
     */
    public function addAssignedUser($accountDemand)
    {
        //
    }

    public function removeAssignedUser($accountDemand)
    {
        //
    }

    public function batchAddAssignedUser($accountCode, $userId, $busineId = '0')
    {
        //
    }

    public function getAdvertiserBalance($accountDemand)
    {
        try {
            $bcId = Arr::get($accountDemand, 'busine_code');
            return $this->tikTokAccount->getAdvertiserBalance(['bc_id' => $bcId, 'fields' => json_encode(['balance_info'])]);
        } catch (\Exception $e) {
            throw new AdvException(500, $e->getMessage(), $e, 0, 0, 'tiktok', 'adv');
        }
    }
}