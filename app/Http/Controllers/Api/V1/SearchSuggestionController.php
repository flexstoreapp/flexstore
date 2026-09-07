<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Storefront\SearchSuggestionRequest;
use App\Http\Resources\Api\V1\SearchSuggestionResource;
use App\Queries\ProductSearchSuggestionsQuery;
use Illuminate\Http\JsonResponse;

final readonly class SearchSuggestionController
{
    public function __invoke(SearchSuggestionRequest $request, ProductSearchSuggestionsQuery $query): JsonResponse
    {
        $result = $query->execute($request->safe()->string('query')->value());

        return response()->json([
            'data' => SearchSuggestionResource::collection($result['suggestions']),
            'meta' => [
                'total' => $result['total'],
                'has_more' => $result['has_more'],
                'query' => $result['query'],
            ],
        ]);
    }
}
