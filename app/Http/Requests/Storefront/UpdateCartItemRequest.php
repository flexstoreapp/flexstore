<?php

declare(strict_types=1);

namespace App\Http\Requests\Storefront;

use Illuminate\Foundation\Http\FormRequest;
use Override;

final class UpdateCartItemRequest extends FormRequest
{
    /**
     * @return array<string, string>
     */
    #[Override]
    public function attributes(): array
    {
        return [
            'quantity' => mb_strtolower(__('Quantity')),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'quantity' => ['required', 'integer', 'min:0'],
        ];
    }
}
