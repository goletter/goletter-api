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

namespace App\Exception;

use Goletter\Resource\Exception\BusinessException as BaseBusinessException;

/**
 * 继承 goletter/hyperf-resource 的业务异常，两个类均由 AppExceptionHandler 处理.
 */
class BusinessException extends BaseBusinessException
{
}
