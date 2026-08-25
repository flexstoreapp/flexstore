<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Queries\UserSearchQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final readonly class UserSearchController
{
    public function __invoke(Request $request, UserSearchQuery $query): JsonResponse
    {
        return response()->json($query->execute($request->query()));
    }
}
