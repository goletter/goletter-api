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
use Hyperf\Amqp\Result;
use Hyperf\Collection\Arr;
use Hyperf\Di\Annotation\Inject;
use PhpAmqpLib\Message\AMQPMessage;
use Throwable;
use function Goletter\Utils\logging;

class AccountRechargeConsumer extends ShardedConsumer
{
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
            $data = ['status' => 0, 'remark' => $exception->getMessage()];
            $accountDemand->update($data);

            return Result::DROP;
        }

        return Result::ACK;
    }

    protected static function queueClass(): string
    {
        return AccountRechargeQueue::class;
    }
}
