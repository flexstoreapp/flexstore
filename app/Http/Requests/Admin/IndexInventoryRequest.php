<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Override;

final class IndexInventoryRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'query' => ['nullable', 'string', 'max:100'],
            'low_stock' => ['nullable', 'boolean'],
            'in_stock' => ['nullable', 'boolean'],
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
            'low_stock' => mb_strtolower(__('Low stock')),
            'in_stock' => mb_strtolower(__('In stock')),
            'per_page' => mb_strtolower(__('Per page')),
        ];
    }
}
