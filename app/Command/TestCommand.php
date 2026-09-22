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

namespace App\Command;

use App\Amqp\Producer\DemoProducer;
use Hyperf\Amqp\Producer;
use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;

use function Goletter\Utils\di;

#[Command]
class TestCommand extends HyperfCommand
{
    public function __construct()
    {
        parent::__construct('test:to');
    }

    public function configure(): void
    {
        parent::configure();
        $this->setDescription('测试：投递 AMQP 消息（Consumer 第一次失败、第二次成功）');
    }

    public function handle()
    {
        $id = uniqid('demo_', true);
        $payload = [
            'id' => $id,
            'xxx' => 1,
        ];

        $message = new DemoProducer($payload);
        $result = di()->get(Producer::class)->produce($message);

        $this->info("已投递 id={$id}，produce=" . var_export($result, true));
        $this->line('请确保 Hyperf 服务已启动（DemoConsumer 在跑）。');
        $this->line('预期：第一次 consume 失败 REQUEUE，第二次 ACK 成功。看日志 channel=amqp');

        return 0;
    }
}
