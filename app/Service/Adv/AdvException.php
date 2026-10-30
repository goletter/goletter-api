<?php

declare(strict_types=1);

namespace App\Service\Adv;

use Goletter\Resource\Exception\BusinessException;
use Hyperf\Collection\Arr;
use Throwable;

class AdvException extends BusinessException
{
    public ?string $accountId = null;
    public ?int $businessId = 0;

    public ?string $platform = null;

    public function __construct(
        int $code = 500,
        ?string $message = null,
        ?Throwable $previous = null,
        ?string $accountId = null,
        ?int $businessId = 0,
        ?string $platform = null,
        string $category = 'adv',
    ) {
        $advMessage = json_decode((string) $message, true);
        $errorUserMsg = Arr::get($advMessage, 'error.error_user_msg', '');
        $errorMessage = Arr::get($advMessage, 'error.message', $message);  // 修正参数

        // 优先使用 error_user_msg，为空时使用 error.message
        $msg = ! empty($errorUserMsg) ? $errorUserMsg : $errorMessage;
        if (is_array($msg)) {
            $msg = json_encode($msg);
        }

        parent::__construct($code, $msg ?? '广告接口权限异常，请稍后重试！', $previous);

        $this->platform = $platform;
        $this->accountId = $accountId;
        $this->businessId = $businessId;
    }
}