<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\DTOs\UpdateOrderShipmentInput;
use Illuminate\Foundation\Http\FormRequest;
use Override;

final class UpdateOrderShipmentRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'tracking_number' => ['nullable', 'string', 'max:255'],
            'tracking_url' => ['nullable', 'url', 'max:2048'],
            'notify_customer' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    public function attributes(): array
    {
        return [
            'tracking_number' => mb_strtolower(__('Tracking number')),
            'tracking_url' => mb_strtolower(__('Tracking URL')),
            'notify_customer' => mb_strtolower(__('Notify customer')),
        ];
    }

    public function toDto(): UpdateOrderShipmentInput
    {
        return UpdateOrderShipmentInput::fromArray($this->validated());
    }
}
