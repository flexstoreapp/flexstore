<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Queries\ApiStoreConfigQuery;
use Illuminate\Http\JsonResponse;

final readonly class StoreConfigController
{
    public function __invoke(ApiStoreConfigQuery $query): JsonResponse
    {
        return response()->json($query->execute());
    }
}
