<?php

declare(strict_types=1);

namespace App\Request\Api\V1\PlatformConfig;

use Hyperf\Validation\Request\FormRequest;

class IndexPlatformConfigRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'page' => 'integer|min:1',
            'pageSize' => 'integer|min:1|max:100',
        ];
    }

    public function attributes(): array
    {
        return [
            'page' => '页码',
            'pageSize' => '每页数量',
        ];
    }
}
