<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Http\Requests\Storefront\SearchSuggestionRequest;
use App\Queries\ProductSearchSuggestionsQuery;
use Illuminate\Http\JsonResponse;

final readonly class SearchSuggestionController
{
    public function __invoke(SearchSuggestionRequest $request, ProductSearchSuggestionsQuery $query): JsonResponse
    {
        $result = $query->execute($request->safe()->string('query')->toString());

        return response()->json($result);
    }
}
