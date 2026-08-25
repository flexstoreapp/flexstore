<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Queries\CategorySearchQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final readonly class CategorySearchController
{
    public function __invoke(Request $request, CategorySearchQuery $query): JsonResponse
    {
        return response()->json($query->execute($request->query()));
    }
}
