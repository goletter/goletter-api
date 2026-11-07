<?php

declare(strict_types=1);

namespace App\Request\Api\V1\AccountInsight;

use Hyperf\Validation\Request\FormRequest;

class UpdateAccountInsightRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id' => 'string|max:100',
            'code' => 'sometimes|required|string|max:100',
            'spend' => 'numeric',
            'start_at' => 'date',
            'end_at' => 'date',
        ];
    }

    public function attributes(): array
    {
        return [
            'tenant_id' => '机构',
            'code' => '账户',
            'spend' => '原消耗',
            'start_at' => '开始时间',
            'end_at' => '结束时间',
        ];
    }
}
