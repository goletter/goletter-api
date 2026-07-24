<?php

declare(strict_types=1);

namespace Goletter\Mtls\Command;

use Goletter\Mtls\Certificate\CertificateGenerationException;
use Goletter\Mtls\Certificate\CertificateGenerator;
use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Di\Annotation\Inject;
use Symfony\Component\Console\Input\InputOption;

#[Command]
class GenerateClientCertCommand extends HyperfCommand
{
    #[Inject]
    protected CertificateGenerator $certificates;

    #[Inject]
    protected ConfigInterface $config;

    public function __construct()
    {
        parent::__construct('mtls:client');
    }

    public function configure(): void
    {
        parent::configure();

        $this
            ->setDescription('Generate an mTLS client certificate and PKCS#12 installer files')
            ->addOption('user', null, InputOption::VALUE_OPTIONAL, 'User/client identifier used for file names and default CN')
            ->addOption('cn', null, InputOption::VALUE_OPTIONAL, 'Client common name')
            ->addOption('password', null, InputOption::VALUE_OPTIONAL, 'Password for client.p12/client.pfx')
            ->addOption('friendly-name', null, InputOption::VALUE_OPTIONAL, 'PKCS#12 friendly name')
            ->addOption('san', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Optional subject alternative name')
            ->addOption('out', null, InputOption::VALUE_OPTIONAL, 'Output directory')
            ->addOption('days', null, InputOption::VALUE_OPTIONAL, 'Certificate validity days');
    }

    public function handle(): int
    {
        $password = $this->optionString('password') ?: (string) $this->config->get('mtls.client_export_password', '');
        if ($password === '') {
            $this->error('Missing --password or MTLS_CLIENT_EXPORT_PASSWORD for client.p12/client.pfx.');

            return self::FAILURE;
        }

        $sans = $this->input->getOption('san');
        $user = $this->optionString('user');

        try {
            $paths = $this->certificates->generateClientCertificate(
                $this->optionString('cn') ?: ($user ?: null),
                $password,
                is_array($sans) ? array_values(array_map('strval', $sans)) : [],
                $this->optionString('out') ?: null,
                $this->optionInt('days'),
                $this->optionString('friendly-name') ?: ($user ?: null),
                $user ?: null
            );

            $this->info('mTLS client certificate generated.');
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
