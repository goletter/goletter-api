<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace App\Trace\Otel;

use OpenTelemetry\Context\ContextInterface;
use OpenTelemetry\SDK\Common\Attribute\AttributesInterface;
use OpenTelemetry\SDK\Trace\SamplerInterface;
use OpenTelemetry\SDK\Trace\SamplingResult;
use OpenTelemetry\SDK\Trace\Span;

/**
 * 单 Trace Span 数上限（装饰器，包在 ParentBased 外层）。
 *
 * 为什么不用上下文传计数：采样发生在 Span 创建之前，Sampler 无法往上下文里写值，
 * 所以这里用进程内"traceId → 计数"窗口表（窗口默认 60s，超窗自动重新计数）。
 * 判定 DROP 的 Span 不创建、不导出；无父的根 Span 不计数，永不触发上限。
 * 表大小超过 10000 时裁掉最旧的一半，防长尾 traceId 泄漏内存。
 */
final class TraceSpanLimitSampler implements SamplerInterface
{
    /** @var array<string, array{0: int, 1: float}> traceId => [计数, 窗口起点] */
    private array $counts = [];

    public function __construct(
        private readonly SamplerInterface $inner,
        private readonly int $maxSpansPerTrace = 500,
        private readonly float $windowSeconds = 60.0,
    ) {
    }

    public function shouldSample(
        ContextInterface $parentContext,
        string $traceId,
        string $spanName,
        int $spanKind,
        AttributesInterface $attributes,
        array $links,
    ): SamplingResult {
        $result = $this->inner->shouldSample($parentContext, $traceId, $spanName, $spanKind, $attributes, $links);
        if ($result->getDecision() !== SamplingResult::RECORD_AND_SAMPLE) {
            return $result;
        }

        // 只对"有父"的 Span 计数：无父（根 Span / 跨进程 remote parent 也是有效父）时不做限制。
        // remote parent 的场景计数从 1 重新开始——这是窗口表的已知近似，可接受。
        if (! Span::fromContext($parentContext)->getContext()->isValid()) {
            return $result;
        }

        if ($this->increment($traceId) > $this->maxSpansPerTrace) {
            return new SamplingResult(SamplingResult::DROP);
        }

        return $result;
    }

    public function getDescription(): string
    {
        return 'TraceSpanLimitSampler{max=' . $this->maxSpansPerTrace . '}+' . $this->inner->getDescription();
    }

    private function increment(string $traceId): int
    {
        $now = microtime(true);
        $entry = $this->counts[$traceId] ?? null;
        if ($entry === null || $now - $entry[1] > $this->windowSeconds) {
            $this->counts[$traceId] = [1, $now];
            if (count($this->counts) > 10000) {
                $this->counts = array_slice($this->counts, -5000, null, true);
            }

            return 1;
        }

        return ++$this->counts[$traceId][0];
    }
}
