<?php

declare(strict_types=1);

namespace App\Model\Filters;

use Goletter\ModelFilter\ModelFilter;

/**
  * @desc AccountDemand 筛选
  * @author yong 2026-11-07
  */
class AccountDemandFilter extends ModelFilter
{
    public function tenantId($tenantId)
    {
        $this->where('tenant_id', 'like', "%{$tenantId}%");
    }

    public function platform($platform)
    {
        $this->where('platform', 'like', "%{$platform}%");
    }

    public function orderNo($orderNo)
    {
        $this->where('order_no', 'like', "%{$orderNo}%");
    }

    public function name($name)
    {
        $this->where('name', 'like', "%{$name}%");
    }

    public function code($code)
    {
        $this->where('code', 'like', "%{$code}%");
    }

    public function busineCode($busineCode)
    {
        $this->where('busine_code', 'like', "%{$busineCode}%");
    }

    public function type($type)
    {
        $this->where('type', $type);
    }

    public function step($step)
    {
        $this->where('step', $step);
    }

    public function currency($currency)
    {
        $this->where('currency', 'like', "%{$currency}%");
    }

    public function timezone($timezone)
    {
        $this->where('timezone', 'like', "%{$timezone}%");
    }

    public function money($money)
    {
        $this->where('money', $money);
    }

    public function email($email)
    {
        $this->where('email', 'like', "%{$email}%");
    }

    public function transactionId($transactionId)
    {
        $this->where('transaction_id', 'like', "%{$transactionId}%");
    }

    public function remark($remark)
    {
        $this->where('remark', 'like', "%{$remark}%");
    }

    public function status($status)
    {
        $this->where('status', $status);
    }

}
