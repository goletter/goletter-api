<?php

declare(strict_types=1);

namespace App\Command\Amqp;

use App\Amqp\AccountQueueRegistry;
use App\Amqp\Sharded\ShardedQueue;

/**
 * 解析 AccountQueueRegistry 业务名，统一未知业务 / 缺参报错文案。
 */
trait ResolvesAccountQueues
{
    /**
     * @return array<string, class-string<ShardedQueue>>
     */
    protected function resolveAccountQueues(?string $name, bool $allowAllWhenEmpty = false): array
    {
        $all = AccountQueueRegistry::all();
        $wantAll = $this->input->hasOption('all') && (bool) $this->input->getOption('all');

        if ($wantAll) {
            return $all;
        }

        if ($name === null || $name === '') {
            if ($allowAllWhenEmpty) {
                return $all;
            }

            $this->error('请指定业务名，或加 --all 处理全部业务。');
            $this->line('可选业务：' . $this->availableQueueNames());
            $this->line('示例：' . $this->exampleCommandLine());

            return [];
        }

        $queueClass = AccountQueueRegistry::get($name);
        if ($queueClass === null) {
            $this->error(sprintf('未知业务「%s」。', $name));
            $this->line('可选业务：' . $this->availableQueueNames());
            $this->line('提示：业务名需与 AccountQueueRegistry 中的 key 一致（如 accountRecharge）。');

            return [];
        }

        return [$name => $queueClass];
    }

    protected function availableQueueNames(): string
    {
        $names = array_keys(AccountQueueRegistry::all());

        return $names === [] ? '(注册表为空)' : implode(', ', $names);
    }

    protected function exampleCommandLine(): string
    {
        return 'php bin/hyperf.php ' . $this->getName() . ' accountRecharge';
    }

    protected function requireForce(string $dangerHint): bool
    {
        if ($this->input->getOption('force')) {
            return true;
        }

        $this->error('已拒绝执行：缺少 --force。');
        $this->line('原因：' . $dangerHint);
        $this->line('确认无误后请加上 --force，例如：');
        $this->line('  ' . $this->exampleCommandLine() . ' --force');

        return false;
    }
}
