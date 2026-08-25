<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Override;

final class IndexShippingRateRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'query' => ['nullable', 'string', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
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
            'page' => mb_strtolower(__('Page')),
            'sort' => mb_strtolower(__('Sort')),
            'direction' => mb_strtolower(__('Direction')),
            'per_page' => mb_strtolower(__('Per page')),
        ];
    }
}
