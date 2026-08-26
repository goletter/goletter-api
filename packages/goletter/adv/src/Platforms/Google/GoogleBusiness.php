<?php

declare(strict_types=1);

namespace Goletter\Adv\Platforms\Google;

/**
 * Google Ads MCC / 经理账户下子客户服务
 */
class GoogleBusiness
{
    public function __construct(
        protected GoogleClient $client
    ) {}

    /**
     * 经理账户下可管理的客户账户列表（GAQL）
     *
     * @return \Generator<int, array>
     */
    public function iterateClientCustomers(
        string $managerCustomerId,
        bool $directOnly = false
    ): \Generator {
        $managerCustomerId = GoogleClient::normalizeCustomerId($managerCustomerId);
        $where = $directOnly
            ? 'customer_client.manager = true AND customer_client.level = 1'
            : 'customer_client.manager = true';

        $query = 'SELECT customer_client.client_customer, customer_client.level, '
            . 'customer_client.manager, customer_client.descriptive_name, '
            . 'customer_client.currency_code, customer_client.status '
            . "FROM customer_client WHERE {$where}";

        yield from $this->client->iterateSearch($managerCustomerId, $query);
    }

    /**
     * 一次性获取经理账户下客户列表
     */
    public function listClientCustomers(string $managerCustomerId, bool $directOnly = false): array
    {
        return iterator_to_array(
            $this->iterateClientCustomers($managerCustomerId, $directOnly)
        );
    }
}
