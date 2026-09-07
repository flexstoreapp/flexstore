<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\ResolveVisitorCartAction;
use App\Http\Resources\Api\V1\ProductCardResource;
use App\Queries\CartCrossSellsQuery;
use App\Utilities\ApiCartToken;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final readonly class CartCrossSellController
{
    private const int LIMIT = 4;

    public function __invoke(
        Request $request,
        ResolveVisitorCartAction $resolveVisitorCart,
        CartCrossSellsQuery $query,
    ): AnonymousResourceCollection {
        $cart = $resolveVisitorCart->handle(ApiCartToken::from($request), $request->user());

        return ProductCardResource::collection($query->execute($cart, self::LIMIT));
    }
}
