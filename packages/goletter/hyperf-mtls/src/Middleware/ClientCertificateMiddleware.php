<?php

declare(strict_types=1);

namespace Goletter\Mtls\Middleware;

use Hyperf\Contract\ConfigInterface;
use Hyperf\HttpServer\Contract\ResponseInterface as ResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

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
            return $this->deny('mTLS client certificate verification failed.');
        }

        $clientCn = $this->header($request, 'cn');
        $fingerprint = $this->normalizeFingerprint($this->header($request, 'fingerprint'));

        if (! $this->allowed('mtls.allowed_client_cns', $clientCn)) {
            return $this->deny('mTLS client certificate CN is not allowed.');
        }

        if (! $this->allowed('mtls.allowed_client_fingerprints', $fingerprint, true)) {
            return $this->deny('mTLS client certificate fingerprint is not allowed.');
        }

        $request = $request
            ->withAttribute('mtls_client_cn', $clientCn)
            ->withAttribute('mtls_client_dn', $this->header($request, 'dn'))
            ->withAttribute('mtls_client_serial', $this->header($request, 'serial'))
            ->withAttribute('mtls_client_fingerprint', $fingerprint);

        return $handler->handle($request);
    }

    private function enabled(): bool
    {
        return filter_var($this->config->get('mtls.verify_client', false), FILTER_VALIDATE_BOOLEAN);
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

    private function deny(string $message): ResponseInterface
    {
        return $this->response->json([
            'code' => 403,
            'message' => $message,
            'data' => null,
        ])->withStatus(403);
    }
}
