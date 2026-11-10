<?php

declare(strict_types=1);

namespace App\Request\FaceBook\V1\AccountInsight;

use Hyperf\Validation\Request\FormRequest;

class ShowAccountInsightRequest extends FormRequest
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
