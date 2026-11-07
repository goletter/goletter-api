<?php

declare(strict_types=1);

namespace App\Resource\Api\V1;

use Goletter\Resource\Resource;
use Hyperf\Collection\Arr;
use Hyperf\Database\Model\Relations\BelongsTo;

class AccountDemandResource extends Resource
{
    protected static array $availableIncludes = ['platformConfig'];

    protected static array $collectionAvailableIncludes = ['platformConfig'];

    public function toArray(): array
    {
        $data = parent::toArray();

        return array_merge($data, []);
    }

    public static function platformConfigQuery(BelongsTo $builder): void
    {
        $builder->select(['id', 'client_secret', 'client_id']);
    }
}