<?php

declare(strict_types=1);

namespace App\Service\Adv;

use App\Constants\LogTypeConstant;
use App\Model\Account;
use App\Model\Busines;
use App\Model\WarnLogs;
use Carbon\Carbon;
use Goletter\Resource\Exception\BusinessException;
use Hyperf\Collection\Arr;
use Hyperf\DbConnection\Db;
use Hyperf\Logger\Logger;
use Random\RandomException;
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
        string $category = LogTypeConstant::Daily,
    ) {
        logging(['platform' => $platform, 'code' => $accountId, 'message' => $message], '第三方平台接口异常', $category, Logger::ERROR);
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

        // 如果检测出这个条件自动关闭app的token状态
        if ($platform == 'facebook' && intval($businessId) > 0 && str_contains((string) $message, 'Application has been deleted')) {
            $busine = Busines::query()->where('id', $businessId)->first();
            $systemToken = Arr::get($busine, 'token');
            $systemUserId = Arr::get($busine, 'system_user_id', 0);
            $systemStatus = Arr::get($busine, 'system_status');

            if ($systemToken && $systemUserId > 0 && $systemStatus) {
                $busine->update(['system_status' => 0]);
            }
        }

        // 增加全局的预警日志（BM 级接口常无 accountId，需判空）
        $account = $accountId !== null && $accountId !== '' && $accountId !== '0'
            ? Account::query()->with(['busine.platform'])->where('code', $accountId)->first()
            : null;
        $platformStatus = Arr::get($account?->busine, 'platform.status', 0);
        $warnType = 0;
        if (str_contains((string) $message, 'Application has been deleted') || str_contains((string) $message, 'the user is not a confirmed user')) {
            $warnType = 1;
        } elseif (str_contains((string) $message, 'API access blocked')) {
            $warnType = 2;
        } elseif (str_contains((string) $message, 'the session for security reasons.')) {
            $warnType = 3;
        }
        if ($account && (int) $platformStatus === 1 && $warnType > 0) {
            $this->setWarnlog($account, $message, $warnType);
        }
    }

    /**
     * @desc 全局捕获异常
     * @param $account
     * @param $message
     * @param int $warnType
     * @return void
     * @throws RandomException
     * @author zhouzhou 2026-07-30
     */
    private function setWarnlog($account, $message, int $warnType)
    {
        $serialNumber = 'AL-' . Carbon::now()->format('Ymd') . '-' . str_pad((string) random_int(0, 99999), 5, '0', STR_PAD_LEFT);
        DB::transaction(function () use ($account, $message, $serialNumber, $warnType) {
            $platformId = Arr::get($account->busine, 'platform_id', 0);
            $accountCode = Arr::get($account, 'code', '');
            $accountId = Arr::get($account, 'id', 0);

            $warn = WarnLogs::query()
                ->where('platform_id', $platformId)
                ->where('warn_type', $warnType)
                ->lockForUpdate()
                ->first();
            if ($warn) {
                $warn->increment('count', 1, [
                    'desc' => $message,
                    'updated_at' => now(),
                    'status' => 0,
                ]);
            } else {
                WarnLogs::create([
                    'platform_id' => $platformId,
                    'code' => $accountCode,
                    'account_id' => $accountId,
                    'warn_type' => $warnType,
                    'desc' => $message,
                    'serial_number' => $serialNumber,
                    'status' => 0,
                    'count' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });
    }
}