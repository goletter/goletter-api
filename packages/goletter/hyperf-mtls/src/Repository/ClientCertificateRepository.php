<?php

declare(strict_types=1);

namespace Goletter\Mtls\Repository;

use Goletter\Mtls\Certificate\CertificateGenerationException;
use mtls\src\Model\ClientCertificate;

class ClientCertificateRepository
{
    /**
     * @param array<string, string> $paths
     */
    public function storeGenerated(string $user, array $paths): ClientCertificate
    {
        $metadata = $this->metadata($paths['client_cert'] ?? '');

        return ClientCertificate::query()->create([
            'user' => $user,
            'cn' => $metadata['cn'] ?: $user,
            'serial' => $metadata['serial'],
            'fingerprint' => $metadata['fingerprint'],
            'status' => ClientCertificate::STATUS_ACTIVE,
            'cert_path' => $paths['client_cert'] ?? '',
            'key_path' => $paths['client_key'] ?? '',
            'p12_path' => $paths['client_p12'] ?? '',
            'pfx_path' => $paths['client_pfx'] ?? '',
            'issued_at' => $metadata['issued_at'],
            'expires_at' => $metadata['expires_at'],
        ]);
    }

    /**
     * @return array{cn: string, serial: string, fingerprint: string, issued_at: string, expires_at: string}
     */
    private function metadata(string $certPath): array
    {
        if ($certPath === '' || ! is_file($certPath)) {
            throw new CertificateGenerationException(sprintf('Client certificate file not found: %s', $certPath));
        }

        $certPem = file_get_contents($certPath);
        if ($certPem === false) {
            throw new CertificateGenerationException(sprintf('Unable to read client certificate: %s', $certPath));
        }

        $parsed = openssl_x509_parse($certPem);
        if (! is_array($parsed)) {
            throw new CertificateGenerationException('Unable to parse client certificate metadata.');
        }

        $fingerprint = openssl_x509_fingerprint($certPem, 'sha256');
        if (! is_string($fingerprint) || $fingerprint === '') {
            throw new CertificateGenerationException('Unable to calculate client certificate fingerprint.');
        }

        return [
            'cn' => (string) ($parsed['subject']['CN'] ?? ''),
            'serial' => (string) ($parsed['serialNumberHex'] ?? $parsed['serialNumber'] ?? ''),
            'fingerprint' => $this->normalizeFingerprint($fingerprint),
            'issued_at' => $this->dateTime((int) ($parsed['validFrom_time_t'] ?? time())),
            'expires_at' => $this->dateTime((int) ($parsed['validTo_time_t'] ?? time())),
        ];
    }

    private function normalizeFingerprint(string $fingerprint): string
    {
        return strtolower(str_replace(':', '', trim($fingerprint)));
    }

    private function dateTime(int $timestamp): string
    {
        return date('Y-m-d H:i:s', $timestamp);
    }
}
