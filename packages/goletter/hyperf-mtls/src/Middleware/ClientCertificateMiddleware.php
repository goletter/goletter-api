<?php

declare(strict_types=1);

namespace Goletter\Mtls\Middleware;

use DateTimeInterface;
use Goletter\Mtls\Model\ClientCertificate;
use Hyperf\Contract\ConfigInterface;
use Hyperf\HttpServer\Contract\ResponseInterface as ResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

class ClientCertificateMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly ConfigInterface $config,
        private readonly ResponseFactory $response
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (! $this->enabled()) {
            return $handler->handle($request);
        }

        $verify = $this->header($request, 'verify');
        if ($verify !== 'SUCCESS') {
            return $this->deny('客户端证书校验失败，请确认已安装并选择有效的客户端证书。');
        }

        $clientCn = $this->header($request, 'cn');
        $serial = $this->header($request, 'serial');
        $fingerprint = $this->normalizeFingerprint($this->header($request, 'fingerprint'));

        if (! $this->allowed('mtls.allowed_client_cns', $clientCn)) {
            return $this->deny('客户端证书 CN 不在允许列表中。');
        }

        if (! $this->allowed('mtls.allowed_client_fingerprints', $fingerprint, true)) {
            return $this->deny('客户端证书指纹不在允许列表中。');
        }

        $record = null;
        if ($this->databaseCheckEnabled()) {
            $record = $this->clientCertificate($fingerprint, $serial);
            if (! $record) {
                return $this->deny('未找到客户端证书记录，请联系管理员重新签发证书。');
            }

            if ($record->status !== ClientCertificate::STATUS_ACTIVE) {
                return $this->deny('客户端证书已被注销，请联系管理员。');
            }

            if ($this->expired($record)) {
                return $this->deny('客户端证书已过期，请联系管理员重新签发证书。');
            }
        }

        $request = $request
            ->withAttribute('mtls_client_cn', $clientCn)
            ->withAttribute('mtls_client_dn', $this->header($request, 'dn'))
            ->withAttribute('mtls_client_serial', $serial)
            ->withAttribute('mtls_client_fingerprint', $fingerprint)
            ->withAttribute('mtls_client_certificate_id', $record?->getKey())
            ->withAttribute('mtls_client_user', $record?->user);

        return $handler->handle($request);
    }

    private function enabled(): bool
    {
        return filter_var($this->config->get('mtls.verify_client', false), FILTER_VALIDATE_BOOLEAN);
    }

    private function databaseCheckEnabled(): bool
    {
        return filter_var($this->config->get('mtls.check_database', true), FILTER_VALIDATE_BOOLEAN);
    }

    private function header(ServerRequestInterface $request, string $name): string
    {
        $header = (string) $this->config->get("mtls.headers.{$name}", '');

        return $header === '' ? '' : trim($request->getHeaderLine($header));
    }

    private function allowed(string $configKey, string $value, bool $normalizeFingerprint = false): bool
    {
        $allowed = $this->config->get($configKey, []);
        if (! is_array($allowed) || $allowed === []) {
            return true;
        }

        $value = $normalizeFingerprint ? $this->normalizeFingerprint($value) : $value;
        $allowed = array_map(
            fn (mixed $item): string => $normalizeFingerprint
                ? $this->normalizeFingerprint((string) $item)
                : trim((string) $item),
            $allowed
        );

        return in_array($value, array_filter($allowed), true);
    }

    private function normalizeFingerprint(string $fingerprint): string
    {
        return strtolower(str_replace(':', '', trim($fingerprint)));
    }

    private function clientCertificate(string $fingerprint, string $serial): ?ClientCertificate
    {
        if ($fingerprint === '' && $serial === '') {
            return null;
        }

        try {
            $query = ClientCertificate::query()->where('fingerprint', $fingerprint);
            if ($serial !== '') {
                $query->orWhere('serial', $serial);
            }

            /** @var null|ClientCertificate $record */
            $record = $query->orderByDesc('id')->first();

            return $record;
        } catch (Throwable) {
            return null;
        }
    }

    private function expired(ClientCertificate $record): bool
    {
        if ($record->expires_at === null) {
            return false;
        }

        if ($record->expires_at instanceof DateTimeInterface) {
            return $record->expires_at->getTimestamp() <= time();
        }

        $timestamp = strtotime((string) $record->expires_at);

        return $timestamp !== false && $timestamp <= time();
    }

    private function deny(string $message): ResponseInterface
    {
        return $this->response->json([
            'code' => 403,
            'message' => $message,
            'data' => null,
        ])->withStatus(403);
    }
}
