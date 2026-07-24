<?php

declare(strict_types=1);

namespace Goletter\Mtls\Command;

use Goletter\Mtls\Certificate\CertificateGenerationException;
use Goletter\Mtls\Certificate\CertificateGenerator;
use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Di\Annotation\Inject;
use Symfony\Component\Console\Input\InputOption;

#[Command]
class GenerateCaCommand extends HyperfCommand
{
    #[Inject]
    protected CertificateGenerator $certificates;

    public function __construct()
    {
        parent::__construct('mtls:ca');
    }

    public function configure(): void
    {
        parent::configure();

        $this
            ->setDescription('Generate an mTLS root CA certificate and private key')
            ->addOption('cn', null, InputOption::VALUE_OPTIONAL, 'CA common name')
            ->addOption('out', null, InputOption::VALUE_OPTIONAL, 'Output directory')
            ->addOption('days', null, InputOption::VALUE_OPTIONAL, 'Certificate validity days');
    }

    public function handle(): int
    {
        try {
            $paths = $this->certificates->generateCa(
                $this->optionString('cn') ?: null,
                $this->optionString('out') ?: null,
                $this->optionInt('days')
            );

            $this->info('mTLS CA generated.');
            $this->printPaths($paths);

            return self::SUCCESS;
        } catch (CertificateGenerationException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }

    private function optionString(string $name): string
    {
        return trim((string) $this->input->getOption($name));
    }

    private function optionInt(string $name): ?int
    {
        $value = $this->optionString($name);

        return $value === '' ? null : (int) $value;
    }

    /**
     * @param array<string, string> $paths
     */
    private function printPaths(array $paths): void
    {
        foreach ($paths as $name => $path) {
            $this->line(sprintf('%s: %s', $name, $path));
        }
    }
}
