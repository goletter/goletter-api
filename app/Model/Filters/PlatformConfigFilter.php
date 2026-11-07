<?php

declare(strict_types=1);

namespace App\Model\Filters;

use Goletter\ModelFilter\ModelFilter;

/**
  * @desc PlatformConfig 筛选
  * @author yong 2026-11-07
  */
class PlatformConfigFilter extends ModelFilter
{
    public function platform($platform)
    {
        $this->where('platform', 'like', "%{$platform}%");
    }

    public function tenantId($tenantId)
    {
        $this->where('tenant_id', 'like', "%{$tenantId}%");
    }

    public function clientId($clientId)
    {
        $this->where('client_id', 'like', "%{$clientId}%");
    }

    public function clientSecret($clientSecret)
    {
        $this->where('client_secret', 'like', "%{$clientSecret}%");
    }

    public function redirectUri($redirectUri)
    {
        $this->where('redirect_uri', 'like', "%{$redirectUri}%");
    }

    public function refreshToken($refreshToken)
    {
        $this->where('refresh_token', 'like', "%{$refreshToken}%");
    }

    public function remark($remark)
    {
        $this->where('remark', 'like', "%{$remark}%");
    }

    public function expireAt($expireAt)
    {
        $this->where('expire_at', $expireAt);
    }

}
