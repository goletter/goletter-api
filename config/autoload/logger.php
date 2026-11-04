<?php

declare(strict_types=1);

use App\Log\Channels;
use App\Log\DailyFileHandler;
use App\Log\EcsEnricher;
use App\Log\EcsJsonFormatter;
use App\Log\ErrorNormalizer;
use App\Log\LogLevel;
use App\Log\RedactLogProcessor;
use App\Log\TraceContextProcessor;

use function Hyperf\Support\env;

/**
 * $name 来自 LoggerFactory::get($name)，也是 runtime/logs/{name}/YYYY-MM-DD.log。
 * 只用 App\Log\Channels 里的几个名字，不要再临时开 access / outbound / trace 这类目录。
 * 同一文件里靠 message 区分：http.access、queue_processed、cli.command、db.query 等。
 * 字段契约见 spec §6（ECS 8.11）：单行 JSON、点号平铺、无数组字段。
 * Processor 顺序：脱敏 → 链路/业务字段 → 环境字段 → 错误展开与一致性约束（ErrorNormalizer 必须最后）。
 *
 * @return array{handler: array<string, mixed>, formatter: array<string, mixed>, processors: list<array<string, mixed>>}
 */
$ecsChannel = static function (string $name): array {
    $base = rtrim((string) (env('LOG_PATH') ?: BASE_PATH . '/runtime/logs'), '/\\');
    $allowed = [
        Channels::HTTP => true,
        Channels::QUEUE => true,
        Channels::CLI => true,
        Channels::APP => true,
        Channels::SQL => true,
    ];
    $category = strtolower($name !== '' ? $name : Channels::APP);
    if (! isset($allowed[$category])) {
        $category = Channels::APP;
    }

    return [
        'handler' => [
            'class' => DailyFileHandler::class,
            'constructor' => [
                'directory' => $base . DIRECTORY_SEPARATOR . $category,
                'level' => LogLevel::parse((string) env('LOG_LEVEL', 'info')),
                'maxFiles' => (int) env('LOG_MAX_FILES', 30),
                'useLocking' => true,
            ],
        ],
        'formatter' => [
            'class' => EcsJsonFormatter::class,
        ],
        'processors' => [
            ['class' => RedactLogProcessor::class],
            ['class' => TraceContextProcessor::class],
            ['class' => EcsEnricher::class],
            ['class' => ErrorNormalizer::class],
        ],
    ];
};

return [
    // Log::app() / http() / queue() / cli() / sql()、LoggerFactory::get('sql') 等
    'default' => $ecsChannel,

    // docs 包可设 DOCS_LOG_GROUP=docs；未登记名仍会落到 app/
    'docs' => static function (string $name) use ($ecsChannel): array {
        return $ecsChannel($name !== '' ? $name : Channels::APP);
    },
];
