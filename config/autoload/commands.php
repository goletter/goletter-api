<?php

declare(strict_types=1);
/**
 * 应用侧注册的命令。
 * goletter/hyperf-openapi 若未以 Composer 包安装（ConfigProvider 未合并），在此显式注册。
 */
use Goletter\OpenApi\Command\OpenApiGenerateCommand;

return [
    OpenApiGenerateCommand::class,
];
