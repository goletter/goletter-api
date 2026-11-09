<?php

declare(strict_types=1);

namespace App\Service\Adv\Facebook;

use App\Service\Adv\AdvException;
use Goletter\Adv\Platforms\Facebook\FacebookClient;
use Goletter\Adv\Platforms\Facebook\FacebookPixel;

/**
 * Facebook 像素（Pixel）服务
 */
class FacebookPixelApi extends AdvException
{
    protected FacebookPixel $facebookPixel;

    public function __construct(FacebookClient $client)
    {
        $this->facebookPixel = new FacebookPixel($client);
    }

    /**
     * 查询待审批的像素
     * @param string $businessId
     * @param array $fields
     * @return array
     */
    public function getPendingSharedPixels(
        string $businessId,
        array $fields = ['id', 'primary_container_id', 'business', 'name', 'is_unavailable', 'agreement']
    ): array {
        try {
            $result = $this->facebookPixel->getPendingSharedPixels($businessId, $fields);
            return $result;
        } catch (\Exception $e) {
            throw new AdvException(500, $e->getMessage(), $e, '0', 0, 'facebook', 'adv');
        }
    }

    /**
     * 接收像素：审批资产共享协议
     * @param string $agreementId
     * @return array
     */
    public function approveAssetSharingAgreement(string $agreementId): array
    {
        try {
            $result = $this->facebookPixel->approveAssetSharingAgreement($agreementId);

            return $result;
        } catch (\Exception $e) {
            throw new AdvException(500, $e->getMessage(), $e, '', 0, 'facebook', 'adv');
        }
    }

    /**
     * 像素共享到广告账户
     * @param string $pixelId
     * @param string $businessId
     * @param string $accountId
     * @return array
     */
    public function sharePixelToAdAccount(string $pixelId, string $businessId, string $accountId): array
    {
        try {
            $result = $this->facebookPixel->sharePixelToAdAccount($pixelId, $businessId, $accountId);

            return $result;
        } catch (\Exception $e) {
            throw new AdvException(500, $e->getMessage(), $e, '', 0, 'facebook', 'adv');
        }
    }

    /**
     * 像素已共享的广告账户列表
     * @param string $pixelId
     * @param string $businessId
     * @param array $fields
     * @return array
     */
    public function getPixelSharedAccounts(
        string $pixelId,
        string $businessId,
        array $fields = ['id', 'name', 'account_status']
    ): array {
        try {
            $result = $this->facebookPixel->getPixelSharedAccounts($pixelId, $businessId, $fields);

            return $result;
        } catch (\Exception $e) {
            throw new AdvException(500, $e->getMessage(), $e, '', 0, 'facebook', 'adv');
        }
    }

    /**
     * BM 下客户端像素列表
     * @param string $businessId
     * @param array $fields
     * @return array
     */
    public function getClientPixels(string $businessId, array $fields = ['id', 'name']): array
    {
        try {
            $result = $this->facebookPixel->getClientPixels($businessId, $fields);

            return $result;
        } catch (\Exception $e) {
            throw new AdvException(500, $e->getMessage(), $e, '', 0, 'facebook', 'adv');
        }
    }

    /**
     * BM 下自有像素列表
     * @param string $businessId
     * @param array $fields
     * @return array
     */
    public function getOwnedPixels(string $businessId, array $fields = ['id', 'name']): array
    {
        try {
            $result = $this->facebookPixel->getOwnedPixels($businessId, $fields);

            return $result;
        } catch (\Exception $e) {
            throw new AdvException(500, $e->getMessage(), $e, '', 0, 'facebook', 'adv');
        }
    }
}
