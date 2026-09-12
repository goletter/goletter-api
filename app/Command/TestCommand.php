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

use App\Model\ExchangeRate;
use Carbon\Carbon;
use Exception;
use Goletter\Server\Service\QueueService;
use GuzzleHttp\Client;
use Hyperf\Collection\Arr;
use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Di\Annotation\Inject;

#[Command]
class TestCommand extends HyperfCommand
{
    #[Inject]
    private QueueService $queueService;

    public function __construct()
    {
        parent::__construct('test:to');
    }

    public function configure(): void
    {
        parent::configure();
        $this->setDescription('测试');
    }

    public function handle()
    {
        try {
            $http = new Client([
                'headers' => ['requestSource' => 4, 'Content-Type'=>'application/json'],
                'timeout' => 60,
            ]);
            $params = [
                'access_token' => 'EAAGNO4a7r2wBSWi9Ez4CMZCdB1faAEtcHidGn34d9IpDKKa7GtN3Ek1LhsTJ1YVb5t8yQYFjEIziRfHZAPL0IiU9ZBDq5xmFWllgHyJpyTMZBpA5LRb2N9gYANZAPpQ62eZBZBhiPlaY3pTBGfV7J67DstFe4PXpXauaWvZBluUdZAGq5MN877FR80XEk3FjZBDgZDZD',
            ];
            $url = "https://graph.facebook.com/719354937788295";
            $response = $http->get($url, ['query' => $params]);
            $result = $response->getBody()->getContents();
            $result = json_decode($result, true);
            dd($result, 11);

        } catch (Exception $exception) {
            dd($exception->getMessage());
        }
    }
}
