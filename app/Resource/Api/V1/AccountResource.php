<?php
declare(strict_types=1);

namespace App\Resource\Api\V1;

use Goletter\Resource\Resource;

class AccountResource extends Resource
{
    protected static array $availableIncludes = [];

    protected static array $collectionAvailableIncludes = [];

    public function toArray(): array
    {
        $data = parent::toArray();

        return array_merge($data, []);
    }
}
