<?php

namespace App\Service\Adv\Facebook;

use App\Service\Adv\AdvException;
use Goletter\Adv\Platforms\Facebook\FacebookClient;
use Goletter\Adv\Platforms\Facebook\FacebookReport;

class FacebookReportApi extends AdvException
{
    protected FacebookReport $facebookReport;
    public function __construct(FacebookClient $client)
    {
        $this->facebookReport = new FacebookReport($client);
    }
}