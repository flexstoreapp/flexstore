<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Admin;

use App\Enums\CouponType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Override;

final class IndexCouponRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'query' => ['nullable', 'string', 'max:100'],
            'type' => ['nullable', Rule::enum(CouponType::class)],
            'status' => ['nullable', 'in:active,inactive,expired,scheduled'],
            'usage' => ['nullable', 'in:unlimited,limited,available'],
            'is_active' => ['nullable', 'boolean'],
            'sort' => ['nullable', 'in:code,value,starts_at,expires_at,used_count,created_at'],
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
            'type' => mb_strtolower(__('Type')),
            'status' => mb_strtolower(__('Status')),
            'usage' => mb_strtolower(__('Usage')),
            'is_active' => mb_strtolower(__('Active')),
            'sort' => mb_strtolower(__('Sort')),
            'direction' => mb_strtolower(__('Direction')),
            'per_page' => mb_strtolower(__('Per page')),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toFilters(): array
    {
        return [
            ...$this->safe()->only(['type', 'status', 'usage', 'is_active', 'sort', 'direction']),
            'search' => $this->safe()->string('query')->value(),
        ];
    }
}
