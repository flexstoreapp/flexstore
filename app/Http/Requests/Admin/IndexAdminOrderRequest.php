<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Override;

final class IndexAdminOrderRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'query' => ['nullable', 'string', 'max:100'],
            'fulfillment_status' => ['nullable', 'string'],
            'payment_status' => ['nullable', 'string'],
            'cancellation_status' => ['nullable', 'string'],
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
            'fulfillment_status' => mb_strtolower(__('Fulfillment status')),
            'payment_status' => mb_strtolower(__('Payment status')),
            'cancellation_status' => mb_strtolower(__('Cancellation status')),
            'sort' => mb_strtolower(__('Sort')),
            'direction' => mb_strtolower(__('Direction')),
            'per_page' => mb_strtolower(__('Per page')),
        ];
    }
}
