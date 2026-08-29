<?php

declare(strict_types=1);

use function Hyperf\Support\env;

return [
    // 临时 ZIP 目录
    'temp_dir' => BASE_PATH . '/runtime/archive',

    // 单次最多打包文件数
    'max_files' => 500,

    // 单次打包总大小上限（字节），默认 500MB
    'max_total_size' => 500 * 1024 * 1024,

    // 临时文件保留时间（秒）
    'temp_ttl' => 3600,

    // ZIP 压缩等级 0-9，-1 表示系统默认
    'compression_level' => -1,

    // 默认上传磁盘（store 未指定 disk 时使用）
    'default_disk' => env('ARCHIVE_DEFAULT_DISK', 'local'),

    // 自动生成远端路径模板，支持 {Ymd} {YmdHis} {uuid} {filename}
    'upload_path' => env('ARCHIVE_UPLOAD_PATH', 'archives/{Ymd}/{uuid}.zip'),

    // 下载地址配置；也可在业务中自定义 ArchiveUrlResolverInterface
    'url' => env('ARCHIVE_URL', ''),

    'disks' => [
        'local' => [
            // 示例：https://example.com/uploads/{path}
            'url' => env('ARCHIVE_LOCAL_URL', env('APP_URL', '') . '/uploads/{path}'),
        ],
        'oss' => [
            'url' => env('ARCHIVE_OSS_URL', ''),
        ],
    ],
];
