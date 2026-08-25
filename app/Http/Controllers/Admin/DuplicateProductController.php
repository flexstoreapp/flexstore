<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\DuplicateProductAction;
use App\Http\Requests\Admin\DuplicateProductRequest;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;

final readonly class DuplicateProductController
{
    public function store(DuplicateProductRequest $request, Product $product, DuplicateProductAction $action): RedirectResponse
    {
        $duplicatedProduct = $action->handle($product, $request->toDto());

        return to_route('admin.products.edit', $duplicatedProduct);
    }
}
