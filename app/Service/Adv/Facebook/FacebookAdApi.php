<?php

declare(strict_types=1);

namespace App\Service\Adv\Facebook;

use App\Service\Adv\AdvException;
use Goletter\Adv\Platforms\Facebook\FacebookAd;
use Goletter\Adv\Platforms\Facebook\FacebookCampaign;
use Goletter\Adv\Platforms\Facebook\FacebookClient;
use InvalidArgumentException;
use RuntimeException;

/**
 * FaceBook 广告投放：图片 → 系列 → 广告组 → 创意 → 广告。
 */
class FacebookAdApi extends AdvException
{
    protected FacebookAd $facebookAd;

    protected FacebookCampaign $facebookCampaign;

    public function __construct(FacebookClient $client)
    {
        $this->facebookAd = new FacebookAd($client);
        $this->facebookCampaign = new FacebookCampaign($client);
    }

    /**
     * 按账户一次性创建并上传广告。
     *
     * 必填：account_id、page_id、primary_text，以及 image_path 或 video_path。
     * 视频广告用 video_path（本地或 http(s)），image_path 作为封面，可省略。
     * image_path 传数组且不少于 2 张时创建轮播广告（最多 10 张）。
     * 状态默认 PAUSED，避免创建后立刻消耗预算。
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function publish(array $data): array
    {
        $accountId = trim((string) ($data['account_id'] ?? $data['ad_account_id'] ?? ''));
        $imagePaths = $this->imagePaths($data['image_path'] ?? '');
        $videoPath = trim((string) ($data['video_path'] ?? ''));
        $pageId = trim((string) ($data['page_id'] ?? ''));
        $primaryText = trim((string) ($data['primary_text'] ?? $data['message'] ?? ''));
        if ($accountId === '' || $pageId === '' || $primaryText === '' || ($imagePaths === [] && $videoPath === '')) {
            throw new InvalidArgumentException('上传广告缺少 account_id、page_id、primary_text，或 image_path / video_path');
        }
        if (count($imagePaths) > 10) {
            throw new InvalidArgumentException('轮播图片最多 10 张');
        }
        if ($videoPath !== '' && count($imagePaths) > 1) {
            throw new InvalidArgumentException('视频广告的 image_path 只能是一张封面');
        }
        foreach ($imagePaths as $imagePath) {
            if (! preg_match('#^https?://#i', $imagePath) && ! is_file($imagePath)) {
                throw new InvalidArgumentException('广告图片不存在: ' . $imagePath);
            }
        }
        if ($videoPath !== '' && ! preg_match('#^https?://#i', $videoPath) && ! is_file($videoPath)) {
            throw new InvalidArgumentException('广告视频不存在: ' . $videoPath);
        }

        try {
            $imageHashes = [];
            foreach ($imagePaths as $imagePath) {
                $image = $this->uploadImage($accountId, $imagePath, (string) ($data['image_mime'] ?? ''), (string) ($data['image_filename'] ?? ''));
                $imageHashes[] = (string) ($image['hash'] ?? '');
            }
            $imageHash = $imageHashes[0] ?? '';
            $videoId = '';
            if ($videoPath !== '') {
                $video = $this->uploadVideo($accountId, $videoPath, (string) ($data['video_filename'] ?? ''));
                $videoId = (string) ($video['video_id'] ?? '');
            }

            $campaign = $this->createCampaign($accountId, [
                'name' => (string) ($data['campaign_name'] ?? 'Page Likes'),
                'objective' => (string) ($data['campaign_objective'] ?? $data['objective'] ?? 'OUTCOME_ENGAGEMENT'),
                'status' => (string) ($data['campaign_status'] ?? 'PAUSED'),
                'buying_type' => (string) ($data['buying_type'] ?? 'AUCTION'),
                'special_ad_categories' => $data['special_ad_categories'] ?? [],
                'is_adset_budget_sharing_enabled' => (bool) ($data['is_adset_budget_sharing_enabled'] ?? false),
            ]);
            $campaignId = (string) ($campaign['id'] ?? '');

            $adsetBody = [
                'name' => (string) ($data['adset_name'] ?? 'FaceBook Page Likes'),
                'campaign_id' => $campaignId,
                'daily_budget' => (int) ($data['daily_budget'] ?? $data['daily_budget_minor'] ?? 1000),
                'billing_event' => (string) ($data['billing_event'] ?? 'IMPRESSIONS'),
                'optimization_goal' => (string) ($data['optimization_goal'] ?? 'PAGE_LIKES'),
                'bid_strategy' => (string) ($data['bid_strategy'] ?? 'LOWEST_COST_WITHOUT_CAP'),
                'destination_type' => (string) ($data['destination_type'] ?? 'ON_PAGE'),
                'promoted_object' => ['page_id' => $pageId],
                'targeting' => $this->targeting($data),
                'status' => (string) ($data['adset_status'] ?? 'PAUSED'),
            ];
            if (! empty($data['attribution_spec'])) {
                $adsetBody['attribution_spec'] = $data['attribution_spec'];
            }
            $adset = $this->createAdset($accountId, $adsetBody);
            $adsetId = (string) ($adset['id'] ?? '');

            $link = trim((string) ($data['link'] ?? $data['page_link_override'] ?? ''));
            if ($link === '') {
                $link = 'https://www.facebook.com/' . $pageId;
            }
            $ctaType = (string) ($data['cta_type'] ?? 'LIKE_PAGE');
            $headline = (string) ($data['headline'] ?? 'Like our FaceBook Page');
            $callToAction = [
                'type' => $ctaType,
                'value' => ['page' => $pageId],
            ];
            if ($videoId !== '') {
                $videoData = [
                    'video_id' => $videoId,
                    'title' => $headline,
                    'message' => $primaryText,
                    'call_to_action' => $callToAction,
                ];
                if ($imageHash !== '') {
                    $videoData['image_hash'] = $imageHash;
                }
                $story = [
                    'page_id' => $pageId,
                    'video_data' => $videoData,
                ];
                $creativeName = (string) ($data['creative_name'] ?? 'Video Creative');
                $adName = (string) ($data['ad_name'] ?? 'Video Ad');
            } elseif (count($imageHashes) >= 2) {
                $cards = [];
                foreach ($imageHashes as $hash) {
                    $cards[] = [
                        'name' => $headline,
                        'link' => $link,
                        'image_hash' => $hash,
                        'call_to_action' => $callToAction,
                    ];
                }
                $story = [
                    'page_id' => $pageId,
                    'link_data' => [
                        'message' => $primaryText,
                        'link' => $link,
                        'child_attachments' => $cards,
                        'multi_share_optimized' => false,
                        'multi_share_end_card' => false,
                    ],
                ];
                $creativeName = (string) ($data['creative_name'] ?? 'Carousel Creative');
                $adName = (string) ($data['ad_name'] ?? 'Carousel Ad');
            } else {
                $story = [
                    'page_id' => $pageId,
                    'link_data' => [
                        'name' => $headline,
                        'message' => $primaryText,
                        'image_hash' => $imageHash,
                        'link' => $link,
                        'call_to_action' => $callToAction,
                    ],
                ];
                $creativeName = (string) ($data['creative_name'] ?? 'Image Creative');
                $adName = (string) ($data['ad_name'] ?? 'Image Ad');
            }
            $creativeBody = [
                'name' => $creativeName,
                'object_story_spec' => $story,
            ];
            if (! empty($data['instagram_user_id'])) {
                $creativeBody['instagram_user_id'] = (string) $data['instagram_user_id'];
            }
            $creative = $this->createCreative($accountId, $creativeBody);
            $creativeId = (string) ($creative['id'] ?? '');

            $ad = $this->createAd($accountId, [
                'name' => $adName,
                'adset_id' => $adsetId,
                'creative' => ['creative_id' => $creativeId],
                'status' => (string) ($data['ad_status'] ?? 'PAUSED'),
            ]);

            $result = [
                'image_hash' => $imageHash,
                'image_hashes' => $imageHashes,
                'video_id' => $videoId,
                'campaign_id' => $campaignId,
                'adset_id' => $adsetId,
                'creative_id' => $creativeId,
                'ad_id' => (string) ($ad['id'] ?? ''),
            ];

            return $result;
        } catch (\Throwable $e) {
            throw $e;
        }
    }

    /**
     * @return array{hash:string,response:array<string,mixed>}
     */
    public function uploadImage(string $accountId, string $filePath, string $mime = '', string $filename = ''): array
    {
        try {
            $response = $this->facebookAd->uploadImage($accountId, $filePath, $mime, $filename);
            $hash = $this->imageHash($response);

            return ['hash' => $hash, 'response' => $response];
        } catch (\Throwable $e) {
            throw $e;
        }
    }

    /**
     * 上传视频并等待 FaceBook 处理完成。
     *
     * @return array{video_id:string,response:array<string,mixed>}
     */
    public function uploadVideo(string $accountId, string $filePath, string $filename = ''): array
    {
        try {
            $response = $this->facebookAd->uploadVideo($accountId, $filePath, $filename);
            $videoId = (string) ($response['id'] ?? '');
            if ($videoId === '') {
                throw new RuntimeException('视频上传没有返回 id');
            }
            $this->waitVideoReady($videoId);

            return ['video_id' => $videoId, 'response' => $response];
        } catch (\Throwable $e) {
            throw $e;
        }
    }

    private function waitVideoReady(string $videoId): void
    {
        $deadline = time() + 90;
        $lastStatus = '';
        while (time() < $deadline) {
            $info = $this->facebookAd->getVideo($videoId);
            $lastStatus = strtolower((string) ($info['status']['video_status'] ?? ''));
            if ($lastStatus === 'ready') {
                return;
            }
            if ($lastStatus === 'error') {
                throw new RuntimeException('视频处理失败: ' . $videoId);
            }
            sleep(3);
        }

        throw new RuntimeException('视频仍在处理中: ' . $videoId . ($lastStatus !== '' ? ' (' . $lastStatus . ')' : ''));
    }

    /**
     * @param array<string, mixed> $data name、objective 必填，其余并入 Graph body
     * @return array<string, mixed>
     */
    public function createCampaign(string $accountId, array $data): array
    {
        $name = trim((string) ($data['name'] ?? ''));
        $objective = trim((string) ($data['objective'] ?? ''));
        if ($name === '' || $objective === '') {
            throw new InvalidArgumentException('创建广告系列缺少 name 或 objective');
        }
        $status = (string) ($data['status'] ?? 'PAUSED');
        unset($data['name'], $data['objective'], $data['status']);
        try {
            $response = $this->facebookCampaign->createCampaign($accountId, $name, $objective, $status, $data);

            return $response;
        } catch (\Throwable $e) {
            throw $e;
        }
    }

    /**
     * @param array<string, mixed> $body
     * @return array<string, mixed>
     */
    public function createAdset(string $accountId, array $body): array
    {
        try {
            $response = $this->facebookAd->createAdset($accountId, $body);

            return $response;
        } catch (\Throwable $e) {
            throw $e;
        }
    }

    /**
     * @param array<string, mixed> $body
     * @return array<string, mixed>
     */
    public function createCreative(string $accountId, array $body): array
    {
        try {
            $response = $this->facebookAd->createCreative($accountId, $body);

            return $response;
        } catch (\Throwable $e) {
            throw $e;
        }
    }

    /**
     * @param array<string, mixed> $body
     * @return array<string, mixed>
     */
    public function createAd(string $accountId, array $body): array
    {
        $request = ['account_id' => $accountId, 'name' => $body['name'] ?? null, 'adset_id' => $body['adset_id'] ?? null];
        try {
            $response = $this->facebookAd->createAd($accountId, $body);

            return $response;
        } catch (\Throwable $e) {
            throw $e;
        }
    }

    /**
     * @param array<string, mixed> $response
     */
    private function imageHash(array $response): string
    {
        $images = $response['images'] ?? [];
        if (! is_array($images) || $images === []) {
            throw new RuntimeException('图片上传没有返回 images');
        }
        $first = reset($images);
        $hash = is_array($first) ? (string) ($first['hash'] ?? '') : '';
        if ($hash === '') {
            throw new RuntimeException('图片上传没有返回 hash');
        }

        return $hash;
    }

    /**
     * @return list<string>
     */
    private function imagePaths(mixed $imagePath): array
    {
        if (is_string($imagePath) || is_int($imagePath)) {
            $imagePath = trim((string) $imagePath);

            return $imagePath === '' ? [] : [$imagePath];
        }
        if (! is_array($imagePath)) {
            throw new InvalidArgumentException('image_path 必须是路径字符串或路径数组');
        }

        $paths = [];
        foreach ($imagePath as $path) {
            if (is_array($path)) {
                $path = $path['path'] ?? $path['image_path'] ?? '';
            }
            $path = trim((string) $path);
            if ($path !== '') {
                $paths[] = $path;
            }
        }

        return $paths;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function targeting(array $data): array
    {
        if (isset($data['targeting']) && is_array($data['targeting'])) {
            return $data['targeting'];
        }

        $countries = $data['target_countries'] ?? ['US'];
        if (! is_array($countries) || $countries === []) {
            throw new InvalidArgumentException('target_countries 必须是非空数组');
        }

        return [
            'age_min' => (int) ($data['age_min'] ?? 18),
            'age_max' => (int) ($data['age_max'] ?? 65),
            'geo_locations' => [
                'countries' => $countries,
                'location_types' => $data['location_types'] ?? ['home', 'recent'],
            ],
        ];
    }
}
