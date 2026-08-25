<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Queries\BrandSearchQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final readonly class BrandSearchController
{
    public function __invoke(Request $request, BrandSearchQuery $query): JsonResponse
    {
        return response()->json($query->execute($request->query()));
    }
}
