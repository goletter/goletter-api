<?php

declare(strict_types=1);

namespace App\Request\FaceBook\V1\Account;

use Hyperf\Validation\Request\FormRequest;

class StoreAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [];
    }

    public function attributes(): array
    {
        return [];
    }
}
