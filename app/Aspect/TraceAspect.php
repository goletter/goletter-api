<?php

declare(strict_types=1);

namespace App\Aspect;

use Hyperf\Di\Annotation\Aspect;
use Hyperf\Di\Aop\AbstractAspect;
use Hyperf\Di\Aop\ProceedingJoinPoint;
use Hyperf\Context\Context;
use OpenTracing\GlobalTracer;
use OpenTracing\Tags;

#[Aspect]
class TraceAspect extends AbstractAspect
{
    private const TRACE_ID_CONTEXT_KEY = 'trace_id';

    public array $classes = [
        'App\Controller\*::*',
        'App\Service\*::*',
        'App\Service\*\*::*',
    ];

    public function process(ProceedingJoinPoint $proceedingJoinPoint)
    {
        $className = $proceedingJoinPoint->className;
        $methodName = $proceedingJoinPoint->methodName;
        $traceId = (string) Context::get(self::TRACE_ID_CONTEXT_KEY, '');
        $tracer = GlobalTracer::get();

        $scope = $tracer->startActiveSpan($this->buildOperationName($className, $methodName), [
            'tags' => [
                Tags\SPAN_KIND => Tags\SPAN_KIND_RPC_SERVER,
                Tags\COMPONENT => 'aop',
                'trace_id' => $traceId,
                'class' => $className,
                'method' => $methodName,
                'layer' => $this->getLayer($className),
            ],
        ]);
        $span = $scope->getSpan();

        $startTime = microtime(true);

        try {
            $result = $proceedingJoinPoint->process();

            $span->setTag('success', true);

            if ($traceId !== '' && $this->isController($className) && is_array($result)) {
                $result = $this->injectTraceId($result, $traceId);
            }

            return $result;
        } catch (\Throwable $e) {
            $span->setTag(Tags\ERROR, true);
            $span->setTag('success', false);
            $span->setTag('error.message', $e->getMessage());
            $span->setTag('error.code', $e->getCode());
            $span->setTag('error.file', $e->getFile() . ':' . $e->getLine());

            throw $e;
        } finally {
            $span->setTag('duration_ms', $this->durationMilliseconds($startTime));
            $scope->close();
        }
    }

    private function buildOperationName(string $className, string $methodName): string
    {
        return sprintf('%s::%s', $className, $methodName);
    }

    private function isController(string $className): bool
    {
        return str_starts_with($className, 'App\Controller\\');
    }

    private function isService(string $className): bool
    {
        return str_starts_with($className, 'App\Service\\');
    }

    private function getLayer(string $className): string
    {
        if ($this->isController($className)) {
            return 'controller';
        }
        if ($this->isService($className)) {
            return 'service';
        }
        return 'unknown';
    }

    private function injectTraceId(array $result, string $traceId): array
    {
        if (!isset($result['trace_id']) && !isset($result['error'])) {
            $result['trace_id'] = $traceId;
        }

        return $result;
    }

    private function durationMilliseconds(float $startTime): float
    {
        return round((microtime(true) - $startTime) * 1000, 3);
    }
}
