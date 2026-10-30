<?php

namespace App\Service\Adv\Facebook;

use App\Service\Adv\AdvException;
use Goletter\Adv\Platforms\Facebook\FacebookBusiness;
use Goletter\Adv\Platforms\Facebook\FacebookClient;

class FacebookBusinessApi extends AdvException
{
    protected FacebookBusiness $facebookBusiness;

    public function __construct(FacebookClient $client)
    {
        $this->facebookBusiness = new FacebookBusiness($client);
    }
}
