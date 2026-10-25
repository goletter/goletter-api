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

use App\Amqp\Producer\AccountDemandProducer;
use Carbon\Carbon;
use Goletter\Adv\AdvFactory;
use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Di\Annotation\Inject;
use function Goletter\Utils\logging;

#[Command]
class TestCommand extends HyperfCommand
{
    #[Inject]
    protected AdvFactory $advFactory;

    public function __construct()
    {
        parent::__construct('test:to');
    }

    public function configure(): void
    {
        parent::configure();
        $this->setDescription('测试');
    }

    public function handle()
    {
        // produce(new AccountDemandProducer(['user_id' => 1, 'status' => 0]));
        /*$message = new AccountDemandProducer(['user_id' => 1, 'status' => 0]);
        $message->setDelayMs(5000);
        produce($message);*/

        logging(['time' => Carbon::now()->toDateTimeString(), 'name' => 123], 'test:to', 'xxx');
    }
}
