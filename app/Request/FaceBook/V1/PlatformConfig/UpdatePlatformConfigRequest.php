<?php

declare(strict_types=1);

namespace App\Request\FaceBook\V1\PlatformConfig;

use Hyperf\Validation\Request\FormRequest;

class UpdatePlatformConfigRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'platform' => 'string|max:32',
            'tenant_id' => 'string|max:100',
            'client_id' => 'string|max:100',
            'client_secret' => 'string|max:100',
            'redirect_uri' => 'string|max:100',
            'token' => 'string|max:300',
            'refresh_token' => 'string|max:200',
            'remark' => 'string|max:200',
            'expire_at' => 'date',
        ];
    }

    public function attributes(): array
    {
        return [
            'platform' => '平台',
            'tenant_id' => '机构',
            'client_id' => '应用Id',
            'client_secret' => '应用Secret',
            'redirect_uri' => '应用回调地址',
            'token' => 'token',
            'refresh_token' => 'refresh_token',
            'remark' => '备注',
            'expire_at' => 'expire_at',
        ];
    }
}
