<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\FulfillmentStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\User;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Support\Facades\DB;

final readonly class RecalculateCustomerLifetimeValueAction
{
    public function handle(?User $user): void
    {
        if (! $user instanceof User) {
            return;
        }

        $user->update([
            'lifetime_value' => $this->calculateLifetimeValue($user),
        ]);
    }

    private function calculateLifetimeValue(User $user): string
    {
        $lifetimeValue = Order::query()
            ->where('customer_id', $user->id)
            ->where('fulfillment_status', FulfillmentStatus::Fulfilled)
            ->whereNull('canceled_at')
            ->whereIn('payment_status', [
                PaymentStatus::Paid,
                PaymentStatus::PartiallyPaid,
                PaymentStatus::Refunded,
                PaymentStatus::PartiallyRefunded,
            ])
            ->selectRaw('COALESCE(SUM(CASE WHEN net_paid_total > 0 THEN net_paid_total / exchange_rate ELSE 0 END), 0) as lifetime_value')
            ->value(DB::raw('lifetime_value'));

        $lifetimeValue = (string) ($lifetimeValue ?? '0');

        return BigDecimal::of($lifetimeValue)
            ->toScale(4, RoundingMode::HalfUp)
            ->toString();
    }
}
