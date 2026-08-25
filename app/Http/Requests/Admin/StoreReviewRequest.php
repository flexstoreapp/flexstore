<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\DTOs\StoreReviewInput;
use App\Models\Product;
use App\Models\User;
use App\Rules\UniqueReviewRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Override;

final class StoreReviewRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'product_id' => ['required', 'integer', Rule::exists(Product::class, 'id'), new UniqueReviewRule()],
            'user_id' => ['required', 'integer', Rule::exists(User::class, 'id')],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'title' => ['sometimes', 'nullable', 'string', 'max:255'],
            'content' => ['required', 'string', 'max:5000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    public function attributes(): array
    {
        return [
            'product_id' => mb_strtolower(__('Product')),
            'user_id' => mb_strtolower(__('User')),
            'rating' => mb_strtolower(__('Rating')),
            'title' => mb_strtolower(__('Title')),
            'content' => mb_strtolower(__('Review')),
        ];
    }

    public function toDto(): StoreReviewInput
    {
        return StoreReviewInput::fromArray($this->validated());
    }
}
