<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\DTOs\StoreOrderShipmentInput;
use App\Models\Order;
use App\Models\OrderItem;
use App\Rules\OrderRequiresShipping;
use App\Rules\ValidShipmentQuantity;
use Illuminate\Container\Attributes\RouteParameter;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Override;

final class StoreOrderShipmentRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(#[RouteParameter('order')] Order $order): array
    {
        return [
            'tracking_number' => ['nullable', 'string', 'max:255'],
            'tracking_url' => ['nullable', 'url', 'max:2048'],
            'items' => ['required', 'array', 'min:1', new OrderRequiresShipping($order)],
            'items.*.order_item_id' => [
                'required',
                'distinct',
                Rule::exists(OrderItem::class, 'id')
                    ->where('order_id', $order->id)
                    ->where('requires_shipping', true),
            ],
            'items.*.quantity' => ['required', 'integer', 'min:1', new ValidShipmentQuantity($order)],
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
            'items' => mb_strtolower(__('Items')),
            'items.*.order_item_id' => mb_strtolower(__('Order item')),
            'items.*.quantity' => mb_strtolower(__('Quantity')),
            'notify_customer' => mb_strtolower(__('Notify customer')),
        ];
    }

    public function toDto(): StoreOrderShipmentInput
    {
        return StoreOrderShipmentInput::fromArray($this->validated());
    }
}
