<?php

declare(strict_types=1);

namespace App\Request\FaceBook\V1\Busine;

use Hyperf\Validation\Request\FormRequest;

class IndexBusineRequest extends FormRequest
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
