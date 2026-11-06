<?php

declare(strict_types=1);

namespace App\Request\Admin\V2;

use Hyperf\Validation\Request\FormRequest;

class AccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            //
        ];
    }

    public function attributes(): array
    {
        return [
            //
        ];
    }
}
