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

use Goletter\Docs\Contract\PlatformFactoryInterface;
use Goletter\Docs\DocsManager;
use Goletter\Docs\PlatformFactory;

return [
    // hyperf-docs 本地 PSR-4 引入时 ConfigProvider 不会自动加载，需手动绑定
    PlatformFactoryInterface::class => PlatformFactory::class,
    PlatformFactory::class => PlatformFactory::class,
    DocsManager::class => DocsManager::class,
];
