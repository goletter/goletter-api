<?php

declare(strict_types=1);

namespace App\Controller;

use Goletter\Mtls\Certificate\CertificateGenerationException;
use Goletter\Mtls\Certificate\CertificateGenerator;
use Goletter\Mtls\Repository\ClientCertificateRepository;
use Hyperf\Di\Annotation\Inject;
use Throwable;

class MtlsCertificateController extends AbstractController
{
    #[Inject]
    protected CertificateGenerator $certificates;

    #[Inject]
    protected ClientCertificateRepository $repository;

    public function store()
    {
        $user = $this->inputString('user');
        if ($user === '') {
            return $this->fail(422, 'Missing user');
        }

        $password = $this->inputString('password');
        if ($password === '') {
            return $this->fail(422, 'Missing password');
        }

        $format = strtolower($this->inputString('format') ?: 'p12');
        if (! in_array($format, ['p12', 'pfx'], true)) {
            return $this->fail(422, 'Invalid format, only p12 or pfx is supported');
        }

        try {
            $paths = $this->certificates->generateClientCertificate(
                $this->inputString('cn') ?: $user,
                $password,
                [],
                null,
                null,
                $this->inputString('friendly_name') ?: $user,
                $user
            );
            $this->repository->storeGenerated($user, $paths);
        } catch (CertificateGenerationException $e) {
            return $this->fail(422, $e->getMessage());
        } catch (Throwable $e) {
            return $this->fail(500, 'Unable to store client certificate record: ' . $e->getMessage());
        }

        $path = $paths['client_' . $format];
        $filename = sprintf('%s.%s', $this->safeFilename($user), $format);

        return $this->response->download($path, $filename);
    }

    private function inputString(string $key): string
    {
        return trim((string) $this->request->input($key, ''));
    }

    private function safeFilename(string $value): string
    {
        $filename = preg_replace('/[^A-Za-z0-9._-]+/', '-', trim($value));
        $filename = trim((string) $filename, '.-_');

        return $filename === '' ? 'client' : $filename;
    }
}
