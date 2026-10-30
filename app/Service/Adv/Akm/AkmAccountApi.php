<?php

namespace App\Service\Adv\Akm;

use App\Service\Adv\AdvException;
use App\Service\Adv\Interface\AccountApi;
use Goletter\Akm\ApplicationApi;
use Goletter\Akm\AkmClient;
use Hyperf\Collection\Arr;

class AkmAccountApi extends AdvException implements AccountApi
{
    protected ApplicationApi $appApi;

    public function __construct(AkmClient $client)
    {
        $this->appApi = new ApplicationApi($client);
    }

    /**
     * 获取广告账户详情
     * @param string $accountId
     * @return array|null
     */
    public function getAccount(string $accountId): array
    {
        try {
            $list = $this->appApi->tiktokAccounts([
                'current_page' => 1,
                'page_size' => 10,
                'account_id'=>$accountId,
            ]);
            $item = Arr::get($list,'data.list.0',[]);
            $status = Arr::get($item, 'status');
            $accountStatus = ($status == 59) ? 1 : 2;

            $result = [
                'account_status' => $accountStatus,
                'amount_spent' => 0,
                'spend_cap' => 0,
                'balance' => 0,
                'spend_balance' => 0
            ];
            if($item) $result['spend_balance'] = $item['balance'] * 100;
            return $result;
        } catch (\Exception $e) {
            throw new AdvException(500, $e->getMessage(), $e, $accountId, 0, 'Akm', 'adv');
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
            $this->appApi->tiktokRename([
                'applications' => [
                    [
                        'account_id' => $accountId,
                        'new_account_name' => $name,
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            throw new AdvException(500, $e->getMessage(), $e, $accountId, 0, 'Akm', 'adv');
        }
    }


    /**
     * 开户
     * @param $account
     * @return void
     */
    public function open($accountDemand): void
    {
        $accountDemand->update(['status' => 2]);
    }

    /**
     * 广告账户充值
     * @return array
     */
    public function recharge($accountDemand): void
    {
        $accountId = $accountDemand->code;
        try {
             $result = $this->appApi->tiktokAmountSpend([
                'is_prepay' => false,
                'applications' => [
                    [
                        'account_id' => $accountId,
                        'currency' => '0',
                        'type' => '1',
                        'amount' => $accountDemand->money,
                    ]
                ]
            ]);
            $applyId = Arr::get($result, 'data.apply_id', 0);
            $accountDemand->update(['transaction_id' => $applyId]);
        } catch (\Exception $e) {
            throw new AdvException(500, $e->getMessage(), $e, $accountId, 0, 'Akm', 'adv');
        }
    }

    /**
     * 账户减款
     * @return array
     */
    public function deduction($accountDemand): void
    {
        $accountId = $accountDemand->code;
        try {
            $result = $this->appApi->tiktokAmountSpend([
                'is_prepay' => false,
                'applications' => [
                    [
                        'account_id' => $accountId,
                        'currency' => '0',
                        'type' => '2',
                        'amount' => $accountDemand->money,
                    ]
                ]
            ]);
            $applyId = Arr::get($result, 'data.apply_id', 0);
            $accountDemand->update(['transaction_id' => $applyId]);
        } catch (\Exception $e) {
            throw new AdvException(500, $e->getMessage(), $e, $accountId, 0, 'Akm', 'adv');
        }
    }

    /**
     * 广告账户清零
     * @param $accountId
     * @param $money
     * @return array
     */
    public function reset($accountDemand): void
    {
        $accountId = $accountDemand->code;
        try {
            $result = $this->appApi->tiktokAmountSpend([
                'is_prepay' => false,
                'applications' => [
                    [
                        'account_id' => $accountId,
                        'currency' => '0',
                        'type' => '3',
                        'amount' => 0,
                    ]
                ]
            ]);
            $applyId = Arr::get($result, 'data.apply_id', 0);
            $accountDemand->update(['transaction_id' => $applyId]);
        } catch (\Exception $e) {
            throw new AdvException(500, $e->getMessage(), $e, $accountId, 0, 'Akm', 'adv');
        }
    }

    /**
     * 回收
     * @param $accountDemand
     * @return void
     */
    public function recycle($accountDemand): void
    {
        //
    }

    /**
     * 广告账户绑定
     * @param $accountDemand
     * @return array
     */
    public function bind($accountDemand): void
    {
        $accountId = $accountDemand->code;
        try {
            $result = $this->appApi->tiktokBindBc([
                'applications' => [
                    [
                        'account_id' => $accountId,
                        'bind_bc' => [
                            ['role' => '1', 'bc' => $accountDemand->email]
                        ],
                    ]
                ]
            ]);
            $applyId = Arr::get($result, 'data.apply_id', 0);
            $accountDemand->update(['transaction_id' => $applyId]);
        } catch (\Exception $e) {
            throw new AdvException(500, $e->getMessage(), $e, $accountId, 0, 'Akm', 'adv');
        }
    }

    /**
     * 广告账户解绑
     * @param $accountDemand
     * @return array
     */
    public function unBind($accountDemand): void
    {
        $accountId = $accountDemand->code;
        try {
            $result = $this->appApi->tiktokBindBc([
                'applications' => [
                    [
                        'account_id' => $accountId,
                        'unbind_bc' => [$accountDemand->email],
                    ]
                ]
            ]);
            $applyId = Arr::get($result, 'data.apply_id', 0);
            $accountDemand->update(['transaction_id' => $applyId]);
        } catch (\Exception $e) {
            throw new AdvException(500, $e->getMessage(), $e, $accountId, 0, 'Akm', 'adv');
        }
    }

    /**
     * 查询结果
     * @param $accountDemand
     * @return array
     */
    public function operateRecords($accountDemand, $category = '1') {
        $accountId = $accountDemand->code;
        // category: 1:额度管理，2：更改名称，3：绑定Bm，4：绑定像素，7：绑定bc_id, 8:绑定邮箱
        try {
            $result = $this->appApi->operateRecords([
                'category' => $category,
                'medium' => '5',
                'account_id' => $accountId,
                'apply_ids' => $accountDemand->apply_id,
                'current_page' => '1',
                'page_size' => '100'
            ]);
            return Arr::get($result, 'data.list.0', []);
        } catch (\Exception $e) {
            throw new AdvException(500, $e->getMessage(), $e, $accountId, 0, 'Akm', 'adv');
        }
    }

    public function pauseAdCampaign($accountDemand): void
    {
        // TODO: Implement pauseAdCampaign() method.
    }

    public function updateAdCampaignStatus($accountDemand, $status): void
    {
        // TODO: Implement updateAdCampaignStatus() method.
    }

    public function addAssignedUser($accountDemand)
    {
        //
    }

    public function removeAssignedUser($accountDemand)
    {
        //
    }
}