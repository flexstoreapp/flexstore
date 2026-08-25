<?php

declare(strict_types=1);

namespace App\Http\Requests\Storefront;

use App\Enums\FulfillmentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Override;

final class ShowAccountOrdersRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['sometimes', 'nullable', 'string', Rule::in([...array_map(fn (FulfillmentStatus $status): string => $status->value, FulfillmentStatus::cases()), 'cancelled'])],
        ];
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    public function attributes(): array
    {
        return [
            'status' => mb_strtolower(__('Status')),
        ];
    }
}
