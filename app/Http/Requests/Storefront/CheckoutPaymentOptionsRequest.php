<?php

declare(strict_types=1);

namespace App\Http\Requests\Storefront;

use Illuminate\Foundation\Http\FormRequest;
use Override;

final class CheckoutPaymentOptionsRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'address' => ['sometimes', 'nullable', 'array'],
            'address.country_code' => ['nullable', 'string', 'size:2'],
            'address.state' => ['nullable', 'string', 'max:255'],
            'address.postal_code' => ['nullable', 'string', 'max:20'],
        ];
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    public function attributes(): array
    {
        return [
            'address' => mb_strtolower(__('Address')),
            'address.country_code' => mb_strtolower(__('Country')),
            'address.state' => mb_strtolower(__('State')),
            'address.postal_code' => mb_strtolower(__('Postal code')),
        ];
    }
}
