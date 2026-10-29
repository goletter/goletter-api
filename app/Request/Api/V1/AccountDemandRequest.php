<?php

declare(strict_types=1);

namespace App\Request\Api\V1;

use Hyperf\Validation\Request\FormRequest;

class AccountDemandRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'platform' => 'required',
        ];
    }

    public function attributes(): array
    {
        return [
            'platform' => '平台',
        ];
    }
}
