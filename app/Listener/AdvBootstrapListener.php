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

namespace App\Listener;

use Goletter\Observability\Log\Log;
use Goletter\Adv\Platforms\Facebook\FacebookClient;
use Goletter\Adv\Platforms\TikTok\TikTokClient;
use Hyperf\Event\Annotation\Listener;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Framework\Event\BootApplication;

#[Listener]
class AdvBootstrapListener implements ListenerInterface
{
    public function listen(): array
    {
        return [BootApplication::class];
    }

    public function process(object $event): void
    {
        FacebookClient::setAppUsageHandler(static function (array $usage, string $token): void {
            //
        });

        FacebookClient::setCallLogHandler(static function (array $log): void {
            Log::app()->error('facebook-adv', [...$log]);
        });

        TikTokClient::setCallLogHandler(function (array $log): void {
            Log::app()->error('tiktok-adv', [...$log]);
        });
    }
}
