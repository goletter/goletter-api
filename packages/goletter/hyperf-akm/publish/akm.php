<?php

declare(strict_types=1);

use function Hyperf\Support\env;

return [
    'base_uri' => env('AKM_BASE_URL', env('CKM_BASE_URL', 'https://akms-openapi.bluemediagroup.cn')),
    'timeout' => (float) env('AKM_TIMEOUT', 10),
    // 是否记录每次请求耗时（毫秒）；默认关闭。开启后写入 call_logs（需 hyperf-utils logging）
    'log_request' => (bool) env('AKM_LOG_REQUEST', false),
    // 'default_headers' => [],
];
