<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Queries\RegionSearchQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final readonly class RegionSearchController
{
    public function __invoke(Request $request, RegionSearchQuery $query): JsonResponse
    {
        return response()->json($query->execute($request->query()));
    }
}
