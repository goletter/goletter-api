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

use App\Model\Account;
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
        // $account = Account::query()->where('code', $accountId)->first();

        try {
            $item = $this->facebookAccount->getAccount($accountId);
            return $item;
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
}
