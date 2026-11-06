<?php

declare(strict_types=1);

use function Hyperf\Support\env;

/**
 * 链路追踪 / 访问日志 / 脱敏（logger processors 与 TraceMiddleware 读取）。
 * OTEL endpoint 为空时仍会创建本地 Span（日志有 trace.id），只是不导出 OTLP。
 */
return [
    'otel' => [
        'endpoint' => env('OTEL_EXPORTER_OTLP_ENDPOINT', ''),
        'protocol' => env('OTEL_EXPORTER_OTLP_PROTOCOL', 'http/protobuf'),
        'headers' => env('OTEL_EXPORTER_OTLP_HEADERS', ''),
        'compression' => env('OTEL_EXPORTER_OTLP_COMPRESSION', 'none'),
        'timeout_ms' => (int) env('OTEL_EXPORTER_OTLP_TIMEOUT_MS', 10000),
        'sampler' => env('OTEL_TRACES_SAMPLER', 'parentbased_traceidratio'),
        'sampler_arg' => (float) env('OTEL_TRACES_SAMPLER_ARG', 1.0),
        'max_queue_size' => (int) env('OTEL_BSP_MAX_QUEUE_SIZE', 2048),
        'flush_interval_ms' => (int) env('OTEL_BSP_SCHEDULE_DELAY', 2000),
        'max_export_batch' => (int) env('OTEL_BSP_MAX_EXPORT_BATCH_SIZE', 512),
        'max_spans_per_trace' => (int) env('OTEL_MAX_SPANS_PER_TRACE', 500),
        'service_name' => env('OTEL_SERVICE_NAME', env('APP_NAME', 'hyperf-base-service')),
        'service_version' => env('OTEL_SERVICE_VERSION', 'dev'),
        'module' => env('OTEL_SERVICE_MODULE', ''),
        'host_name' => env('OTEL_HOST_NAME', gethostname() ?: 'unknown'),
        'host_ip' => env('OTEL_HOST_IP', '127.0.0.1'),
        'code_point_server' => (bool) env('OTEL_CODE_POINT_SERVER', true),
    ],

    'access' => [
        'skip_methods' => ['OPTIONS'],
        'skip_paths' => ['/health', '/favicon.ico'],
        'slow_ms' => (float) env('TRACE_ACCESS_SLOW_MS', 1000),
        'log_request_payload' => (bool) env('TRACE_LOG_REQUEST_PAYLOAD', true),
        'log_response_payload' => (bool) env('TRACE_LOG_RESPONSE_PAYLOAD', true),
    ],

    'log' => [
        'timestamp_precision' => env('TRACE_LOG_TIMESTAMP_PRECISION', 'ms'),
        'payload_bytes' => (int) env('TRACE_LOG_PAYLOAD_BYTES', 8192),
        'stacktrace' => (bool) env('TRACE_LOG_STACKTRACE', true),
        'stacktrace_frames' => (int) env('TRACE_LOG_STACKTRACE_FRAMES', 50),
    ],

    'sql' => [
        'log_all' => (bool) env('TRACE_SQL_LOG_ALL', false),
        'log_bindings' => (bool) env('TRACE_SQL_LOG_BINDINGS', false),
        'slow_ms' => (float) env('TRACE_SQL_SLOW_MS', 500),
    ],

    // 脱敏键（大小写不敏感）；空数组则用 RedactLogProcessor 内置默认
    'redact_keys' => [],
];
