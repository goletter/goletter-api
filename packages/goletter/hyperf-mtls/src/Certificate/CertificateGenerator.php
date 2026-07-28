<?php

declare(strict_types=1);

namespace Goletter\Mtls\Certificate;

use Hyperf\Contract\ConfigInterface;
use OpenSSLAsymmetricKey;
use OpenSSLCertificate;
use OpenSSLCertificateSigningRequest;

class CertificateGenerator
{
    public function __construct(
        private readonly ConfigInterface $config,
        private readonly OpenSslConfigBuilder $configBuilder
    ) {
    }

    /**
     * @return array<string, string>
     */
    public function generateCa(?string $commonName = null, ?string $outputDir = null, ?int $days = null): array
    {
        $baseOutputDir = $this->prepareOutputDir($outputDir);
        $caOutputDir = $this->prepareOutputDir($baseOutputDir . '/ca');
        $commonName ??= (string) $this->config->get('mtls.ca_common_name', 'Goletter Root CA');
        $days ??= (int) $this->config->get('mtls.ca_days', 3650);

        return $this->withOpenSslConfig([], function (string $configPath) use ($commonName, $days, $caOutputDir): array {
            $privateKey = $this->createPrivateKey((int) $this->config->get('mtls.ca_key_bits', 4096));
            $csr = $this->createCsr($commonName, $privateKey, $configPath);
            $cert = $this->signCertificate($csr, null, $privateKey, $days, $configPath, 'v3_ca');

            $keyPath = $caOutputDir . '/ca.key';
            $certPath = $caOutputDir . '/ca.crt';

            $this->writePrivateKey($privateKey, $keyPath);
            $this->writeCertificate($cert, $certPath);

            return [
                'ca_key' => $keyPath,
                'ca_cert' => $certPath,
            ];
        });
    }

    /**
     * @param list<string> $sans
     * @return array<string, string>
     */
    public function generateClientCertificate(
        ?string $commonName = null,
        string $password = '',
        array $sans = [],
        ?string $outputDir = null,
        ?int $days = null,
        ?string $friendlyName = null,
        ?string $clientName = null
    ): array {
        if ($password === '') {
            throw new CertificateGenerationException('Client PKCS#12 export password is required.');
        }

        $baseOutputDir = $this->prepareOutputDir($outputDir);
        $commonName ??= (string) $this->config->get('mtls.client_common_name', 'goletter-client');
        $days ??= (int) $this->config->get('mtls.cert_days', 365);
        $friendlyName ??= (string) $this->config->get('mtls.client_friendly_name', 'Goletter Client');
        $fileName = $this->normalizeFileName($clientName ?: $commonName);
        $clientOutputDir = $this->prepareOutputDir($baseOutputDir . '/clients/' . $fileName);

        $paths = $this->generateSignedCertificate(
            $fileName,
            'client',
            $commonName,
            $sans,
            $baseOutputDir,
            $clientOutputDir,
            $days,
            'client_cert'
        );
        $p12Path = sprintf('%s/%s.p12', $clientOutputDir, $fileName);
        $pfxPath = sprintf('%s/%s.pfx', $clientOutputDir, $fileName);

        $this->exportPkcs12(
            $paths['client_cert'],
            $paths['client_key'],
            $baseOutputDir . '/ca/ca.crt',
            $p12Path,
            $password,
            $friendlyName
        );

        if (! copy($p12Path, $pfxPath)) {
            throw new CertificateGenerationException(sprintf('Unable to create PKCS#12 alias file: %s', $pfxPath));
        }
        chmod($pfxPath, 0600);

        return $paths + [
            'client_p12' => $p12Path,
            'client_pfx' => $pfxPath,
        ];
    }

    /**
     * @param list<string> $sans
     * @return array<string, string>
     */
    private function generateSignedCertificate(
        string $fileName,
        string $resultPrefix,
        string $commonName,
        array $sans,
        string $baseOutputDir,
        string $certificateOutputDir,
        int $days,
        string $extension
    ): array {
        $caCertPath = $baseOutputDir . '/ca/ca.crt';
        $caKeyPath = $baseOutputDir . '/ca/ca.key';
        if (! is_file($caCertPath) || ! is_file($caKeyPath)) {
            throw new CertificateGenerationException(sprintf(
                'CA files not found in %s/ca. Generate them with mtls:ca first.',
                $baseOutputDir
            ));
        }

        return $this->withOpenSslConfig($sans, function (string $configPath) use (
            $fileName,
            $resultPrefix,
            $commonName,
            $days,
            $certificateOutputDir,
            $extension,
            $caCertPath,
            $caKeyPath
        ): array {
            $privateKey = $this->createPrivateKey((int) $this->config->get('mtls.key_bits', 2048));
            $csr = $this->createCsr($commonName, $privateKey, $configPath);
            $cert = $this->signCertificate(
                $csr,
                file_get_contents($caCertPath),
                file_get_contents($caKeyPath),
                $days,
                $configPath,
                $extension
            );

            $keyPath = sprintf('%s/%s.key', $certificateOutputDir, $fileName);
            $certPath = sprintf('%s/%s.crt', $certificateOutputDir, $fileName);

            $this->writePrivateKey($privateKey, $keyPath);
            $this->writeCertificate($cert, $certPath);

            return [
                sprintf('%s_key', $resultPrefix) => $keyPath,
                sprintf('%s_cert', $resultPrefix) => $certPath,
            ];
        });
    }

    private function createPrivateKey(int $bits): OpenSSLAsymmetricKey
    {
        $key = openssl_pkey_new([
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
            'private_key_bits' => $bits,
        ]);

        if ($key === false) {
            throw new CertificateGenerationException('Unable to generate private key: ' . $this->opensslErrors());
        }

        return $key;
    }

    private function createCsr(
        string $commonName,
        OpenSSLAsymmetricKey $privateKey,
        string $configPath
    ): OpenSSLCertificateSigningRequest {
        $csr = openssl_csr_new($this->distinguishedName($commonName), $privateKey, [
            'digest_alg' => 'sha256',
            'config' => $configPath,
        ]);

        if ($csr === false) {
            throw new CertificateGenerationException('Unable to generate CSR: ' . $this->opensslErrors());
        }

        return $csr;
    }

    private function signCertificate(
        OpenSSLCertificateSigningRequest $csr,
        OpenSSLCertificate|string|null $caCertificate,
        OpenSSLAsymmetricKey|string $caPrivateKey,
        int $days,
        string $configPath,
        string $extension
    ): OpenSSLCertificate {
        $certificate = openssl_csr_sign($csr, $caCertificate, $caPrivateKey, $days, [
            'digest_alg' => 'sha256',
            'config' => $configPath,
            'x509_extensions' => $extension,
        ], random_int(1, PHP_INT_MAX));

        if ($certificate === false) {
            throw new CertificateGenerationException('Unable to sign certificate: ' . $this->opensslErrors());
        }

        return $certificate;
    }

    private function writePrivateKey(OpenSSLAsymmetricKey $privateKey, string $path): void
    {
        if (! openssl_pkey_export($privateKey, $pem)) {
            throw new CertificateGenerationException('Unable to export private key: ' . $this->opensslErrors());
        }

        $this->writeFile($path, $pem, 0600);
    }

    private function writeCertificate(OpenSSLCertificate $certificate, string $path): void
    {
        if (! openssl_x509_export($certificate, $pem)) {
            throw new CertificateGenerationException('Unable to export certificate: ' . $this->opensslErrors());
        }

        $this->writeFile($path, $pem, 0644);
    }

    private function exportPkcs12(
        string $certPath,
        string $keyPath,
        string $caCertPath,
        string $outputPath,
        string $password,
        string $friendlyName
    ): void {
        // OpenSSL 3 defaults to AES-256-CBC + SHA-256 MAC. macOS Keychain /
        // Security.framework still expect legacy 3DES + SHA-1 and report a
        // misleading "MAC verification failed (wrong password?)" otherwise.
        $command = [
            $this->resolveOpenSslBinary(),
            'pkcs12',
            '-export',
            '-inkey',
            $keyPath,
            '-in',
            $certPath,
            '-certfile',
            $caCertPath,
            '-out',
            $outputPath,
            '-name',
            $friendlyName,
            '-passout',
            'pass:' . $password,
            '-keypbe',
            'PBE-SHA1-3DES',
            '-certpbe',
            'PBE-SHA1-3DES',
            '-macalg',
            'sha1',
        ];

        [$exitCode, $stderr] = $this->runOpenSslCommand($command);
        if ($exitCode !== 0 || ! is_file($outputPath)) {
            throw new CertificateGenerationException(sprintf(
                'Unable to export PKCS#12 file: %s',
                $stderr !== '' ? $stderr : 'openssl pkcs12 -export failed'
            ));
        }

        chmod($outputPath, 0600);
    }

    private function resolveOpenSslBinary(): string
    {
        $candidates = ['openssl'];
        foreach (['/usr/local/bin/openssl', '/opt/homebrew/bin/openssl', '/usr/bin/openssl'] as $path) {
            if (is_executable($path)) {
                array_unshift($candidates, $path);
            }
        }

        foreach (array_unique($candidates) as $binary) {
            [$exitCode] = $this->runOpenSslCommand([$binary, 'version']);
            if ($exitCode === 0) {
                return $binary;
            }
        }

        throw new CertificateGenerationException(
            'Unable to find a working openssl binary for macOS-compatible PKCS#12 export.'
        );
    }

    /**
     * @param list<string> $command
     * @return array{0: int, 1: string}
     */
    private function runOpenSslCommand(array $command): array
    {
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($command, $descriptors, $pipes, null, null, ['bypass_shell' => true]);
        if (! is_resource($process)) {
            return [1, 'Unable to start openssl process.'];
        }

        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]) ?: '';
        $stderr = stream_get_contents($pipes[2]) ?: '';
        fclose($pipes[1]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);
        $message = trim($stderr !== '' ? $stderr : $stdout);

        return [$exitCode, $message];
    }

    /**
     * @param list<string> $sans
     * @template T
     * @param callable(string): T $callback
     * @return T
     */
    private function withOpenSslConfig(array $sans, callable $callback): mixed
    {
        $path = tempnam(sys_get_temp_dir(), 'goletter_mtls_');
        if ($path === false) {
            throw new CertificateGenerationException('Unable to create temporary OpenSSL config file.');
        }

        file_put_contents($path, $this->configBuilder->build($sans));

        try {
            return $callback($path);
        } finally {
            @unlink($path);
        }
    }

    private function prepareOutputDir(?string $outputDir): string
    {
        $outputDir = rtrim((string) ($outputDir ?: $this->config->get('mtls.output_dir', BASE_PATH . '/runtime/certs')), '/');
        if ($outputDir === '') {
            throw new CertificateGenerationException('Certificate output directory is required.');
        }

        if (! is_dir($outputDir) && ! mkdir($outputDir, 0755, true) && ! is_dir($outputDir)) {
            throw new CertificateGenerationException(sprintf('Unable to create output directory: %s', $outputDir));
        }

        return $outputDir;
    }

    /**
     * @return array<string, string>
     */
    private function distinguishedName(string $commonName): array
    {
        $dn = [
            'countryName' => (string) $this->config->get('mtls.country_name', 'CN'),
            'stateOrProvinceName' => (string) $this->config->get('mtls.state_or_province_name', ''),
            'localityName' => (string) $this->config->get('mtls.locality_name', ''),
            'organizationName' => (string) $this->config->get('mtls.organization_name', 'Goletter'),
            'organizationalUnitName' => (string) $this->config->get('mtls.organizational_unit_name', ''),
            'commonName' => $commonName,
        ];

        return array_filter($dn, static fn (string $value): bool => $value !== '');
    }

    private function writeFile(string $path, string $content, int $permissions): void
    {
        if (file_put_contents($path, $content) === false) {
            throw new CertificateGenerationException(sprintf('Unable to write file: %s', $path));
        }

        chmod($path, $permissions);
    }

    private function normalizeFileName(string $name): string
    {
        $fileName = preg_replace('/[^A-Za-z0-9._-]+/', '-', trim($name));
        $fileName = trim((string) $fileName, '.-_');

        if ($fileName === '') {
            throw new CertificateGenerationException('Client name must contain at least one ASCII letter, digit, dot, underscore, or dash.');
        }

        return $fileName;
    }

    private function opensslErrors(): string
    {
        $errors = [];
        while ($message = openssl_error_string()) {
            $errors[] = $message;
        }

        return $errors === [] ? 'unknown OpenSSL error' : implode('; ', $errors);
    }
}
