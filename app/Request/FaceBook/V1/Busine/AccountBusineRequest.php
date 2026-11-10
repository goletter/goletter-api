<?php

declare(strict_types=1);

namespace App\Request\FaceBook\V1\Busine;

use Hyperf\Validation\Request\FormRequest;

class AccountBusineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'token' => 'required|string',
            'busine_code' => 'required|string',
        ];
    }

    public function attributes(): array
    {
        return [
            'token' => '第三方Token',
            'busine_code' => 'Bm Code',
        ];
    }
}
