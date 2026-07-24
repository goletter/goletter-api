<?php

declare(strict_types=1);

namespace Goletter\Mtls\Certificate;

class OpenSslConfigBuilder
{
    /**
     * @param list<string> $sans
     */
    public function build(array $sans = []): string
    {
        $altNames = $this->buildAltNames($sans);
        $clientSubjectAltName = $altNames === '' ? '' : 'subjectAltName = @alt_names';

        return trim(<<<CONF
[ req ]
default_md = sha256
prompt = no
distinguished_name = dn
string_mask = utf8only

[ dn ]
CN = ignored

[ v3_ca ]
basicConstraints = critical, CA:TRUE
keyUsage = critical, keyCertSign, cRLSign
subjectKeyIdentifier = hash
authorityKeyIdentifier = keyid:always,issuer

[ client_cert ]
basicConstraints = critical, CA:FALSE
keyUsage = critical, digitalSignature, keyEncipherment
extendedKeyUsage = clientAuth
subjectKeyIdentifier = hash
authorityKeyIdentifier = keyid,issuer
{$clientSubjectAltName}
{$altNames}
CONF) . PHP_EOL;
    }

    /**
     * @param list<string> $sans
     */
    private function buildAltNames(array $sans): string
    {
        if ($sans === []) {
            return '';
        }

        $counters = [];
        $lines = ['', '[ alt_names ]'];

        foreach ($sans as $san) {
            $san = trim($san);
            if (! preg_match('/^(DNS|IP|URI|email):(.+)$/i', $san, $matches)) {
                throw new CertificateGenerationException(sprintf(
                    'Invalid SAN "%s". Use DNS:example.com, IP:127.0.0.1, URI:https://example.com, or email:user@example.com.',
                    $san
                ));
            }

            $type = strtolower($matches[1]) === 'email' ? 'email' : strtoupper($matches[1]);
            $value = trim($matches[2]);
            if ($value === '') {
                throw new CertificateGenerationException(sprintf('Invalid empty SAN value for "%s".', $san));
            }

            $counters[$type] = ($counters[$type] ?? 0) + 1;
            $lines[] = sprintf('%s.%d = %s', $type, $counters[$type], $value);
        }

        return implode(PHP_EOL, $lines);
    }
}
