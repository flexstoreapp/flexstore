<?php

declare(strict_types=1);

namespace App\Http\Requests\Storefront;

use Illuminate\Foundation\Http\FormRequest;
use Override;

final class TrackOrderRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'order_number' => ['required', 'integer', 'min:1'],
            'email' => ['required', 'email', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    public function attributes(): array
    {
        return [
            'order_number' => mb_strtolower(__('Order number')),
            'email' => mb_strtolower(__('Email address')),
        ];
    }

    #[Override]
    protected function prepareForValidation(): void
    {
        $orderNumber = $this->input('order_number');

        if (is_string($orderNumber)) {
            $this->merge([
                'order_number' => mb_ltrim(mb_trim($orderNumber), '#'),
            ]);
        }
    }
}
