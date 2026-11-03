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
            'token' => 'required',
            'code' => 'required',
        ];
    }

    public function attributes(): array
    {
        return [
            'token' => 'token',
            'code' => '账户Id',
        ];
    }
}
