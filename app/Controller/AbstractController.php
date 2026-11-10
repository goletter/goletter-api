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

namespace App\Controller;

use App\Exception\ValidateException;
use Goletter\Resource\ApiResponse;
use Hyperf\Context\ApplicationContext;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Hyperf\Validation\Contract\ValidatorFactoryInterface;
use Psr\Container\ContainerInterface;
use ReflectionClass;

abstract class AbstractController
{
    use ApiResponse;

    #[Inject]
    protected ContainerInterface $container;

    #[Inject]
    protected RequestInterface $request;

    #[Inject]
    protected ResponseInterface $response;

    /**
     * 与 Controller 版本目录对齐：App\Controller\FaceBook\V1 → App\Resource\FaceBook\V1.
     */
    protected function resourceNamespace(): string
    {
        $ns = (new ReflectionClass($this))->getNamespaceName();
        if (str_contains($ns, '\Controller\\')) {
            return str_replace('\Controller\\', '\Resource\\', $ns);
        }

        return 'App\Resource';
    }

    /**
     * 自定义控制器验证器.
     * @param mixed ...$arg
     */
    protected function validate(...$arg): void
    {
        $validator = ApplicationContext::getContainer()->get(ValidatorFactoryInterface::class)->make(...$arg);
        if ($validator->fails()) {
            throw new ValidateException($validator->errors()->first(), 422);
        }
    }
}
