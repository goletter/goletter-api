<?php

declare(strict_types=1);

namespace Goletter\Queue\Command;

use Hyperf\AsyncQueue\Driver\DriverFactory;
use Hyperf\Command\Command as HyperfCommand;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Input\InputArgument;

class InfoCommand extends HyperfCommand
{
    public function __construct(protected ContainerInterface $container)
    {
        parent::__construct('queue:ms-info');
    }

    public function configure(): void
    {
        parent::configure();
        $this->setDescription('Show millisecond async queue pool info.');
        $this->addArgument('pool', InputArgument::OPTIONAL, 'Queue pool name', 'ms');
    }

    public function handle(): void
    {
        $pool = (string) $this->input->getArgument('pool');
        $factory = $this->container->get(DriverFactory::class);
        $driver = $factory->get($pool);
        $info = $driver->info();

        $this->info(sprintf('Pool [%s] (%s)', $pool, $driver::class));
        foreach ($info as $key => $value) {
            $this->line(sprintf('  %-10s %s', $key, $value));
        }
    }
}
