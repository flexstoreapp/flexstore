<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\Api\V1\HomepageSectionResource;
use App\Queries\StorefrontHomepageDataQuery;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final readonly class HomepageController
{
    public function __invoke(StorefrontHomepageDataQuery $query): AnonymousResourceCollection
    {
        return HomepageSectionResource::collection($query->execute());
    }
}
