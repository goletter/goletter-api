<?php

declare(strict_types=1);

namespace App\Request\Akm\V1\AccountDemand;

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
        ];
    }

    public function attributes(): array
    {
        return [
            'tenant_id' => '机构',
            'platform' => '平台',
        ];
    }
}
