<?php

declare(strict_types=1);

namespace App\Command;

use App\Exception\BusinessException;
use App\Log\Log;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Command\Annotation\Command;
use PHPUnit\Event\Code\Throwable;
use PHPUnit\Util\Filter;
use PHPUnit\Util\ThrowableToStringMapper;
use Psr\Container\ContainerInterface;

#[Command]
class TestCommand extends HyperfCommand
{
    public function __construct(protected ContainerInterface $container)
    {
        parent::__construct('test:to');
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('Hyperf Demo Command');
    }

    public function handle()
    {
        try {
            throw new BusinessException(422, '订单状态不允许支付');
        } catch (\Throwable $exception) {
            Log::app()->error('order_paid', ['ttxxx' => $exception->getMessage(), 'trace' => $exception->getTraceAsString(), 'order_id' => 1, 'xx' => 222, 'xx333' => 222, 'xxxx' => ['eeee' => 222, 'xeee' => 55555]]);
        }
    }
}
