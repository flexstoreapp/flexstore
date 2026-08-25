<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\DTOs\UpdateShippingCarrierInput;
use App\Enums\ShippingCarrierDriver;
use App\Models\ShippingCarrier;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Override;

final class UpdateShippingCarrierRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'driver' => $this->driverRules(),
            'is_active' => ['sometimes', 'required', 'boolean'],
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

    public function toDto(): UpdateShippingCarrierInput
    {
        return UpdateShippingCarrierInput::fromArray($this->validated());
    }

    /**
     * @return list<mixed>
     */
    private function driverRules(): array
    {
        $rules = ['sometimes', 'required', Rule::enum(ShippingCarrierDriver::class)];

        if ($this->input('driver') !== null && $this->input('driver') !== ShippingCarrierDriver::Manual->value) {
            $carrier = $this->route('carrier');
            $rules[] = Rule::unique('shipping_carriers', 'driver')
                ->ignore($carrier instanceof ShippingCarrier ? $carrier->id : null);
        }

        return $rules;
    }
}
