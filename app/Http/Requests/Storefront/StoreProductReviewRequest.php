<?php

declare(strict_types=1);

namespace App\Http\Requests\Storefront;

use App\DTOs\StoreReviewInput;
use App\Models\Product;
use App\Models\User;
use App\Rules\HasNotReviewedProduct;
use App\Rules\HasPurchasedProduct;
use Illuminate\Container\Attributes\RouteParameter;
use Illuminate\Foundation\Http\FormRequest;
use Override;

final class StoreProductReviewRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(#[RouteParameter('product')] Product $product): array
    {
        $user = $this->user();

        return [
            'rating' => [
                'required',
                'integer',
                'min:1',
                'max:5',
                new HasPurchasedProduct($product, $user),
                new HasNotReviewedProduct($product, $user),
            ],
            'title' => ['nullable', 'string', 'max:255'],
            'content' => ['required', 'string', 'min:5', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    public function attributes(): array
    {
        return [
            'rating' => mb_strtolower(__('Rating')),
            'title' => mb_strtolower(__('Title')),
            'content' => mb_strtolower(__('Review')),
        ];
    }

    public function toDto(Product $product, User $user): StoreReviewInput
    {
        return StoreReviewInput::fromArray([
            ...$this->validated(),
            'product_id' => $product->id,
            'user_id' => $user->id,
        ]);
    }
}
