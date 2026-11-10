<?php
declare(strict_types=1);

namespace App\Resource\FaceBook\V1;

use Goletter\Resource\Resource;

class CatalogResource extends Resource
{
    public function toArray(): array
    {
        $data = parent::toArray();

        return array_merge($data, []);
    }
}
