<?php

declare(strict_types=1);

/**
 * ApiResponse 自动解析 Resource 时的默认命名空间。
 * 带版本的 Controller（App\Controller\Api\V1 等）会覆盖为 App\Resource\Api\V1。
 */
return [
    'namespace' => 'App\\Resource',
];
