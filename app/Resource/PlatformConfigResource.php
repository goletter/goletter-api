<?php
declare(strict_types=1);

namespace App\Resource;

use Goletter\Resource\Resource;

class PlatformConfigResource extends Resource
{
    protected static array $availableIncludes = [];

    protected static array $collectionAvailableIncludes = [];

    public function toArray(): array
    {
        $data = parent::toArray();

        return array_merge($data, []);
    }
}
