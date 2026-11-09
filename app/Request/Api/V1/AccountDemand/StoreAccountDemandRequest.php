<?php

declare(strict_types=1);

namespace App\Request\Api\V1\AccountDemand;

use Hyperf\Validation\Request\FormRequest;

class StoreAccountDemandRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id' => 'string|max:100',
            'platform' => 'string|max:32',
            'order_no' => 'string|max:100',
            'name' => 'string|max:100',
            'product_name' => 'string|max:100',
            'code' => 'string|max:100',
            'busine_code' => 'string|max:100',
            'type' => 'integer',
            'step' => 'integer|in:0,1,2',
            'currency' => 'string|max:50',
            'timezone' => 'string|max:50',
            'money' => 'integer',
            'email' => 'string|max:500',
            'sheel_url' => 'string|max:300',
            'transaction_id' => 'string|max:100',
            'extend' => 'array',
            'remark' => 'string|max:255',
            'status' => 'integer|in:1,2',
        ];
    }

    public function attributes(): array
    {
        return [
            'tenant_id' => '机构',
            'platform' => '平台',
            'order_no' => '需求单号',
            'name' => '账户ming',
            'product_name' => '产品简称',
            'code' => '账户ID',
            'busine_code' => 'busine_code',
            'type' => '类型；2：充值，3：减款；4：清零；6：绑定,  7：解绑，8：像素，9：目录',
            'step' => '步骤',
            'currency' => '货币',
            'timezone' => '时区',
            'money' => '金额',
            'email' => '邮箱/Bm/Bc',
            'sheel_url' => 'sheel_url',
            'transaction_id' => 'transaction_id',
            'extend' => '扩展',
            'remark' => '备注',
            'status' => '状态',
        ];
    }
}
