<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Queries\AdminStoreConfigQuery;
use Illuminate\Http\JsonResponse;

final readonly class StoreConfigController
{
    public function __invoke(AdminStoreConfigQuery $query): JsonResponse
    {
        return response()->json($query->execute());
    }
}
