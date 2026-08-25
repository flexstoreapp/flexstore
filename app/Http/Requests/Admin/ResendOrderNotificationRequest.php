<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\OrderEmailType;
use App\Models\Order;
use App\Models\OrderRefund;
use App\Models\OrderShipment;
use Illuminate\Container\Attributes\RouteParameter;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Override;

final class ResendOrderNotificationRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(#[RouteParameter('order')] Order $order): array
    {
        $type = $this->input('type');

        return [
            'type' => ['required', Rule::enum(OrderEmailType::class)],
            'shipment_id' => [
                Rule::requiredIf(in_array($type, [
                    OrderEmailType::OrderFulfilled->value,
                    OrderEmailType::TrackingUpdated->value,
                ], true)),
                'integer',
                Rule::exists(OrderShipment::class, 'id')->where('order_id', $order->id),
            ],
            'refund_id' => [
                Rule::requiredIf($type === OrderEmailType::OrderRefunded->value),
                'integer',
                Rule::exists(OrderRefund::class, 'id')->where('order_id', $order->id),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    public function attributes(): array
    {
        return [
            'type' => mb_strtolower(__('Email type')),
            'shipment_id' => mb_strtolower(__('Shipment')),
            'refund_id' => mb_strtolower(__('Refund')),
        ];
    }
}
