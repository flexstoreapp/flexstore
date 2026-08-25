<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\DTOs\ProcessRefundInput;
use App\Models\Order;
use App\Models\OrderItem;
use App\Rules\ValidRefundQuantity;
use App\Rules\ValidRefundShippingAmount;
use App\Rules\ValidRefundTotal;
use Illuminate\Container\Attributes\RouteParameter;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Override;

final class StoreRefundRequest extends FormRequest
{
    public function authorize(#[RouteParameter('order')] Order $order): bool
    {
        abort_unless($order->is_refundable, 410, __('This order cannot be refunded.'));

        return true;
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    public function attributes(): array
    {
        return [
            'shipping_amount' => mb_strtolower(__('Shipping amount')),
            'restocking_fee' => mb_strtolower(__('Restocking fee')),
            'total' => mb_strtolower(__('Total')),
            'is_manual_total' => mb_strtolower(__('Manual total')),
            'restock' => mb_strtolower(__('Restock')),
            'notify_customer' => mb_strtolower(__('Notify customer')),
            'refund_method' => mb_strtolower(__('Refund method')),
            'reason' => mb_strtolower(__('Reason')),
            'items' => mb_strtolower(__('Items')),
            'items.*.order_item_id' => mb_strtolower(__('Order item')),
            'items.*.quantity' => mb_strtolower(__('Quantity')),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(#[RouteParameter('order')] Order $order): array
    {
        return [
            'shipping_amount' => ['sometimes', 'numeric', 'min:0', new ValidRefundShippingAmount($order)],
            'restocking_fee' => ['sometimes', 'numeric', 'min:0'],
            'total' => ['sometimes', 'numeric', 'min:0', new ValidRefundTotal($order)],
            'is_manual_total' => ['sometimes', 'boolean'],
            'restock' => ['sometimes', 'boolean'],
            'notify_customer' => ['sometimes', 'boolean'],
            'refund_method' => ['required', Rule::in(['process_through_gateway', 'record_only'])],
            'reason' => ['nullable', 'string'],
            'items' => ['array'],
            'items.*.order_item_id' => [
                'required',
                'distinct',
                Rule::exists(OrderItem::class, 'id')->where('order_id', $order->id),
            ],
            'items.*.quantity' => ['required', 'integer', 'min:1', new ValidRefundQuantity($order)],
        ];
    }

    public function toDto(): ProcessRefundInput
    {
        return ProcessRefundInput::fromArray($this->validated());
    }

    #[Override]
    protected function prepareForValidation(): void
    {
        if (! $this->has('total')) {
            $this->merge(['total' => '0']);
        }
    }
}
