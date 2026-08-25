<?php

declare(strict_types=1);

namespace App\Queries;

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\User;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

final readonly class CustomerStatsByEmailQuery
{
    /**
     * @return array{name: string|null, orders_count: int, lifetime_value: string, created_at: string}|null
     */
    public function execute(string $email): ?array
    {
        $user = User::query()
            ->where('email', $email)
            ->first(['id', 'name', 'lifetime_value', 'created_at']);

        $ordersCount = Order::query()
            ->where('customer_email', $email)
            ->whereNull('canceled_at')
            ->count();

        if (! $user instanceof User && $ordersCount === 0) {
            return null;
        }

        return [
            'name' => $user?->name,
            'orders_count' => $ordersCount,
            'lifetime_value' => $this->formatLifetimeValue(
                $user instanceof User ? $user->lifetime_value : $this->aggregateLifetimeValue($email),
            ),
            'created_at' => $this->formatCreatedAt(
                $user instanceof User ? $user->created_at : $this->earliestOrderCreatedAt($email),
            ),
        ];
    }

    private function aggregateLifetimeValue(string $email): string
    {
        $value = Order::query()
            ->where('customer_email', $email)
            ->whereNull('canceled_at')
            ->whereIn('payment_status', [
                PaymentStatus::Paid,
                PaymentStatus::PartiallyPaid,
                PaymentStatus::Refunded,
                PaymentStatus::PartiallyRefunded,
            ])
            ->selectRaw('COALESCE(SUM(CASE WHEN net_paid_total > 0 THEN net_paid_total / exchange_rate ELSE 0 END), 0) as lifetime_value')
            ->value(DB::raw('lifetime_value'));

        return (string) $value;
    }

    private function earliestOrderCreatedAt(string $email): CarbonInterface
    {
        return Order::query()
            ->where('customer_email', $email)
            ->oldest('created_at')
            ->value('created_at');
    }

    private function formatLifetimeValue(string $value): string
    {
        return BigDecimal::of($value)->toScale(4, RoundingMode::HalfUp)->toString();
    }

    private function formatCreatedAt(CarbonInterface $date): string
    {
        return $date->toIso8601String();
    }
}
