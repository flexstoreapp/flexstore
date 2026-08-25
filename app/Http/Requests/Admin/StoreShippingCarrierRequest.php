<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\DTOs\StoreShippingCarrierInput;
use App\Enums\ShippingCarrierDriver;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Override;

final class StoreShippingCarrierRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'driver' => $this->driverRules(),
            'is_active' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    public function attributes(): array
    {
        return [
            'name' => mb_strtolower(__('Name')),
            'driver' => mb_strtolower(__('Driver')),
            'is_active' => mb_strtolower(__('Active')),
        ];
    }

    public function toDto(): StoreShippingCarrierInput
    {
        return StoreShippingCarrierInput::fromArray($this->validated());
    }

    /**
     * @return list<mixed>
     */
    private function driverRules(): array
    {
        $rules = ['required', Rule::enum(ShippingCarrierDriver::class)];

        if ($this->input('driver') !== ShippingCarrierDriver::Manual->value && $this->input('driver') !== null) {
            $rules[] = Rule::unique('shipping_carriers', 'driver');
        }

        return $rules;
    }
}
