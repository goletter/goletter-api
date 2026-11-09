<?php

declare(strict_types=1);

namespace App\Request\Api\V1\Account;

use Hyperf\Validation\Request\FormRequest;

class ShowAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id' => 'required|integer|min:1',
        ];
    }

    public function attributes(): array
    {
        return [
            'id' => 'ID',
        ];
    }
}
