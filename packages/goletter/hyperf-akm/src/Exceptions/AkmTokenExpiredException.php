<?php

declare(strict_types=1);

namespace Goletter\Akm\Exceptions;

use Goletter\Adv\Exceptions\TokenExpiredExceptionInterface;

class AkmTokenExpiredException extends AkmsApiException implements TokenExpiredExceptionInterface
{
}
