<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace App\Amqp\Consumer;

use App\Amqp\Queue\AccountRechargeQueue;
use App\Amqp\Sharded\ShardedConsumer;
use App\Model\AccountDemand;
use App\Service\Adv\AdvResolver;
use App\Service\Webhook\WebhookNotifier;
use Hyperf\Amqp\Result;
use Hyperf\Collection\Arr;
use Hyperf\Di\Annotation\Inject;
use PhpAmqpLib\Message\AMQPMessage;
use Throwable;

use function Goletter\Utils\logging;

class AccountRechargeConsumer extends ShardedConsumer
{
    #[Inject]
    private AdvResolver $advResolver;

    #[Inject]
    private WebhookNotifier $webhookNotifier;

    protected function handle(mixed $data, AMQPMessage $message): Result
    {
        $accountDemandId = Arr::get($data, 'account_demand_id');
        $accountDemand = AccountDemand::query()->where('id', $accountDemandId)->where('status', 0)->first();

        if (! $accountDemand) {
            return Result::ACK;
        }

        try {
            $accountDemand->update(['status' => 1]);
            $token = Arr::get($data, 'access_token');
            $adv = $this->advResolver->resolveAdv('docs', $token);
            $adv->account()->recharge($accountDemand);
        } catch (Throwable $exception) {
            $accountDemand->update([
                'status' => 0,
                'remark' => $exception->getMessage(),
            ]);
            $this->safeNotify($accountDemand->fresh() ?? $accountDemand, 'account_demand.failed');

            return Result::DROP;
        }

        $this->safeNotify($accountDemand->fresh() ?? $accountDemand, 'account_demand.succeeded');

        return Result::ACK;
    }

    protected function safeNotify(AccountDemand $accountDemand, string $event): void
    {
        try {
            $this->webhookNotifier->notify($accountDemand, $event);
        } catch (Throwable $e) {
            logging([
                'event' => 'webhook_notify_error',
                'webhook_event' => $event,
                'account_demand_id' => $accountDemand->id,
                'error' => $e->getMessage(),
            ], 'Webhook 通知异常（不影响主流程）', 'webhook');
        }
    }

    protected static function queueClass(): string
    {
        return AccountRechargeQueue::class;
    }
}
