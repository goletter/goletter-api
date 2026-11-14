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

namespace App\Command;

use App\Model\PlatformConfig;
use Carbon\Carbon;
use Exception;
use Goletter\Docs\DocsManager;
use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Di\Annotation\Inject;
use Psr\Container\ContainerInterface;

#[Command]
class GoogleTokenCommand extends HyperfCommand
{
    #[Inject]
    protected DocsManager $docs;

    public function __construct(protected ContainerInterface $container)
    {
        parent::__construct('goletter:google-token');
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('Google Token 刷新');
    }

    public function handle()
    {
        $platformConfigs = PlatformConfig::query()->get();

        foreach ($platformConfigs as $platformConfig) {
            if (! $platformConfig->refresh_token) {
                return;
            }
            // $minutesDiff = Carbon::now()->diffInMinutes(Carbon::parse($platformConfig->expire_at), true);

            try {
                $platform = $this->docs->make('google', [
                    'client_id' => $platformConfig->client_id,
                    'client_secret' => $platformConfig->client_secret,
                    'redirect_uri' => $platformConfig->redirect_uri,
                    'drive_root_folder' => 'Goletter',
                ]);
                $token = $platform->refreshToken($platformConfig->refresh_token);
                $platformConfig->update([
                    'token' => $token['access_token'] ?? '',
                    'refresh_token' => $token['refresh_token'] ?? '',
                    'expire_at' => Carbon::now()->toDateTimeString(),
                ]);
                $this->line('更新成功');
            } catch (Exception $e) {
                $this->error($e->getMessage());
            }
        }
    }
}
