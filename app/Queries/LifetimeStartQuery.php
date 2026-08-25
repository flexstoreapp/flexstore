<?php

declare(strict_types=1);

namespace App\Queries;

use App\Models\Order;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Date;

final readonly class LifetimeStartQuery
{
    public function execute(): CarbonInterface
    {
        $earliest = $this->earliest(Order::query()->min('created_at'))
            ?? $this->earliest(User::query()->min('created_at'));

        return $earliest ?? Date::now()->startOfDay();
    }

    private function earliest(mixed $value): ?CarbonInterface
    {
        if (! is_string($value) && ! is_numeric($value)) {
            return null;
        }

        return Date::parse($value)->startOfDay();
    }
}
