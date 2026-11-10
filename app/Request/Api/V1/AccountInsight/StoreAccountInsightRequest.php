<?php

declare(strict_types=1);

namespace App\Request\Api\V1\AccountInsight;

use Hyperf\Validation\Request\FormRequest;

class StoreAccountInsightRequest extends FormRequest
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
