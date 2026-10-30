<?php

declare(strict_types=1);

namespace App\Service\Adv\Facebook;

use App\Service\Adv\AdvException;
use Goletter\Adv\Platforms\Facebook\FacebookAd;
use Goletter\Adv\Platforms\Facebook\FacebookCampaign;
use Goletter\Adv\Platforms\Facebook\FacebookClient;

/**
 * Facebook 广告投放：图片 → 系列 → 广告组 → 创意 → 广告。
 */
class FacebookAdApi extends AdvException
{
    protected FacebookAd $facebookAd;

    protected FacebookCampaign $facebookCampaign;

    public function __construct(FacebookClient $client)
    {
        $this->facebookAd = new FacebookAd($client);
        $this->facebookCampaign = new FacebookCampaign($client);
    }
}
