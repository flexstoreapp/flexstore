<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Override;

final class IndexReviewRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'query' => ['nullable', 'string', 'max:100'],
            'product' => ['nullable', 'integer'],
            'status' => ['nullable', 'string'],
            'rating' => ['nullable', 'integer', 'min:1', 'max:5'],
            'sort' => ['nullable', 'string'],
            'direction' => ['nullable', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    public function attributes(): array
    {
        return [
            'query' => mb_strtolower(__('Search query')),
            'product' => mb_strtolower(__('Product')),
            'status' => mb_strtolower(__('Status')),
            'rating' => mb_strtolower(__('Rating')),
            'sort' => mb_strtolower(__('Sort')),
            'direction' => mb_strtolower(__('Direction')),
            'per_page' => mb_strtolower(__('Per page')),
        ];
    }
}
