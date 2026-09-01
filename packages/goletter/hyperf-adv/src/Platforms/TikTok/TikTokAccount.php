<?php

namespace Goletter\Adv\Platforms\TikTok;

/**
 * TikTok 账户服务
 *
 * 这里只实现一个最小封装：获取广告主下的广告账户 / 基本信息。
 * 如需更多能力（创建广告主、绑定资产等），可在此类基础上扩展。
 */
class TikTokAccount
{
    public function __construct(
        protected TikTokClient $client
    ) {}

    /**
     * 获取广告主信息
     *
     * 对应 TikTok Business API 的 advertiser 信息查询接口。
     *
     * @param string $advertiserId 广告主 ID
     */
    public function getAdvertiser(string $advertiserId): array
    {
        $response = $this->client->get('/open_api/v1.3/advertiser/info/', [
            'advertiser_ids' => json_encode([$advertiserId]),
        ]);
        return $response['data']['list'][0] ?? [];
    }

    /**
     * 批量获取广告主信息
     *
     * @param array $advertiserIds 广告主 ID 列表
     * @return array
     */
    public function listAdvertisers(array $advertiserIds): array
    {
        if (empty($advertiserIds)) {
            return [];
        }

        $response = $this->client->get('/open_api/v1.3/advertiser/info/', [
            'advertiser_ids' => json_encode(array_values($advertiserIds)),
        ]);

        return $response['data']['list'] ?? [];
    }

    /**
     * 获取广告系列列表
     *
     * @param string $advertiserId 广告主 ID
     */
    public function getCampaign(string $advertiserId): array
    {
        $response = $this->client->get('/open_api/v1.3/campaign/get/', [
            'advertiser_id' => $advertiserId,
        ]);
        return $response;
    }

    /**
     * 修改广告系列状态
     * @param string $advertiserId 广告主 ID
     */
    public function updateCampaignStatus(array $params): array
    {
        $response = $this->client->post('/open_api/v1.3/campaign/status/update/', $params);
        return $response;
    }

    /**
     * 更新广告账户名称
     * @param string $accountId
     * @param string $name
     */
    public function updateAccountName(string $accountId, string $name)
    {
        return [];
    }

    public function getTransaction(array $params): array
    {
        $response = $this->client->get('/open_api/v1.3/bc/get/', $params);
        return $response;
    }


    public function getAdvertiserBalance(array $params): array
    {
        $response = $this->client->get('/open_api/v1.3/advertiser/balance/get/', $params);
        return $response;
    }
}

