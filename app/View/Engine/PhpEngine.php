<?php

declare(strict_types=1);

namespace App\View\Engine;

use Hyperf\View\Engine\EngineInterface;
use InvalidArgumentException;

/**
 * 轻量 PHP 模板引擎（不依赖 duncan3dc/blade）。
 * 模板路径：storage/view/{dot.path}.blade.php 或 .php
 */
class PhpEngine implements EngineInterface
{
    public function render(string $template, array $data, array $config): string
    {
        $base = rtrim((string) ($config['view_path'] ?? ''), '/\\') . DIRECTORY_SEPARATOR
            . str_replace('.', DIRECTORY_SEPARATOR, $template);

        $file = null;
        foreach (['.blade.php', '.php', '.html'] as $ext) {
            if (is_file($base . $ext)) {
                $file = $base . $ext;
                break;
            }
        }

        if ($file === null) {
            throw new InvalidArgumentException(sprintf('View [%s] not found under %s', $template, $config['view_path'] ?? ''));
        }

        return (static function (string $__file, array $__data): string {
            extract($__data, EXTR_SKIP);
            ob_start();
            include $__file;
            return (string) ob_get_clean();
        })($file, $data);
    }
}
