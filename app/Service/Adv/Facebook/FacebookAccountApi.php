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

namespace App\Service\Adv\Facebook;

use App\Service\Adv\AdvException;
use Exception;
use Goletter\Adv\Platforms\Facebook\FacebookAccount;
use Goletter\Adv\Platforms\Facebook\FacebookCampaign;
use Goletter\Adv\Platforms\Facebook\FacebookClient;
use Hyperf\Collection\Arr;

use function Hyperf\Config\config;

class FacebookAccountApi extends AdvException
{
    protected FacebookAccount $facebookAccount;

    protected FacebookCampaign $facebookCampaign;

    public function __construct(FacebookClient $client)
    {
        $this->facebookAccount = new FacebookAccount($client);
        $this->facebookCampaign = new FacebookCampaign($client);
    }

    /**
     * 获取广告账户详情.
     * @return null|array
     */
    public function getAccount(string $accountId): array
    {
        try {
            $item = $this->facebookAccount->getAccount($accountId);
            $amountSpent = Arr::get($item, 'amount_spent', 0);
            $spendCap = Arr::get($item, 'spend_cap', 0);
            $timezone = Arr::get($item, 'timezone_offset_hours_utc', '');
            $currency = Arr::get($item, 'currency', '');
            $name = Arr::get($item, 'name', '');
            $accountStatus = Arr::get($item, 'account_status');
            if ($accountStatus == 9) {
                $accountStatus = 3;
            }
            if ($accountStatus >= 100) {
                $accountStatus = 2;
            }

            return [
                'account_status' => $accountStatus,
                'amount_spent' => $amountSpent,
                'spend_cap' => $spendCap,
                'balance' => Arr::get($item, 'balance', 0),
                'spend_balance' => bcsub((string) $spendCap, (string) $amountSpent, 2),
                'timezone' => Arr::get(config('wewallads.TIME_ZONE'), (string) $timezone),
                'currency' => $currency,
                'name' => $name,
            ];
        } catch (Exception $e) {
            throw new AdvException(500, $e->getMessage(), $e, $accountId, 0, 'facebook', 'adv');
        }
    }

    /**
     * 更改账户昵称
     * @param string $accountId
     * @param string $name
     * @return void
     */
    public function updateAccountName(string $accountId, string $name): void
    {
        try {
            $this->facebookAccount->updateAccountName($accountId, $name);
        } catch (\Exception $e) {
            throw new AdvException(500, $e->getMessage(), $e, $accountId, 0, 'facebook', 'adv');
        }
    }

    /**
     * 开户
     * @return void
     */
    public function open()
    {
        //
    }

    /**
     * 充值
     * @param $accountId
     * @param $spendCap
     * @return void
     */
    public function recharge($accountId, $spendCap)
    {
        try {
            $this->facebookAccount->updateAccountSpendCap($accountId, $spendCap);
        } catch (\Exception $e) {
            throw new AdvException(500, $e->getMessage(), $e, $accountId, 0, 'facebook', 'adv');
        }
    }

    /**
     * 减款
     * @param $accountId
     * @param $spendCap
     * @return void
     */
    public function deduction($accountId, $spendCap)
    {
        try {
            $this->facebookAccount->updateAccountSpendCap($accountId, $spendCap);
        } catch (\Exception $e) {
            throw new AdvException(500, $e->getMessage(), $e, $accountId, 0, 'facebook', 'adv');
        }
    }

    /**
     * 清零
     * @param $accountId
     * @return void
     */
    public function reset($accountId)
    {
        try {
            $deleteResult = $this->facebookAccount->deleteAccountSpendCapAction($accountId,'delete');

            $this->facebookAccount->updateAccountSpendCap($accountId, '0.01');
        } catch (\Exception $e) {
            throw new AdvException(500, $e->getMessage(), $e, $accountId, 0, 'facebook', 'adv');
        }
    }
}
