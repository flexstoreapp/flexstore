<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Actions\DuplicateProductAction;
use App\Http\Requests\Admin\DuplicateProductRequest;
use App\Http\Resources\Api\V1\Admin\AdminProductResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final readonly class DuplicateProductController
{
    public function store(DuplicateProductRequest $request, Product $product, DuplicateProductAction $action): JsonResponse
    {
        $duplicate = $action->handle($product, $request->toDto());

        return (new AdminProductResource($duplicate->load(AdminProductResource::RELATIONS)))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }
}
