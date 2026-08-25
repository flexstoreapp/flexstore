<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Models\Product;
use App\Models\ProductVariant;
use Exception;

final class InsufficientStockException extends Exception
{
    public function __construct(
        public readonly Product $product,
        public readonly ?ProductVariant $variant,
        public readonly int $requested,
        public readonly int $available,
    ) {
        $productName = $variant instanceof ProductVariant
            ? "{$product->title} ({$variant->title})"
            : $product->title;

        parent::__construct(
            "Insufficient stock for {$productName}. Requested: {$requested}, Available: {$available}"
        );
    }
}
