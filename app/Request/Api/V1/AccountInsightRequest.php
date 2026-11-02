<?php

declare(strict_types=1);

namespace App\Request\Api\V1;

use Hyperf\Validation\Request\FormRequest;

class AccountInsightRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => 'required',
            'token' => 'required',
            'busine_code' => 'required',
        ];
    }

    public function attributes(): array
    {
        return [
            'code' => '账户ID',
            'token' => 'Token',
            'busine_code' => 'Bm ID',
        ];
    }
}
