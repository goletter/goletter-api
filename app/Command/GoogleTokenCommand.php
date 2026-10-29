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

use App\Model\DocsPlatformConfig;
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
        $docsPlatformConfigs = DocsPlatformConfig::query()->get();

        foreach ($docsPlatformConfigs as $index => $docsPlatformConfig) {
            if (! $docsPlatformConfig->refresh_token) {
                return;
            }
            // $minutesDiff = Carbon::now()->diffInMinutes(Carbon::parse($docsPlatformConfig->expire_at), true);

            try {

                $platform = $this->docs->make('google', [
                    'client_id' => $docsPlatformConfig->client_id,
                    'client_secret' => $docsPlatformConfig->client_secret,
                    'redirect_uri' => $docsPlatformConfig->redirect_uri,
                    'drive_root_folder' => 'Goletter',
                ]);
                $token = $platform->refreshToken($docsPlatformConfig->refresh_token);
                $docsPlatformConfig->update([
                    'token' => $token['access_token'] ?? '',
                    'refresh_token' => $token['refresh_token'] ?? '',
                    'expire_at' => Carbon::now()->toDateTimeString(),
                ]);
                $this->line("{$index}==============={$token}");
            } catch (Exception $e) {
                $this->error($e->getMessage());
            }
        }
    }
}
