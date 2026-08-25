<?php

declare(strict_types=1);

namespace App\Queries;

use App\Models\PaymentGateway;
use Illuminate\Database\Eloquent\Collection;

final readonly class ActivePaymentGatewayListQuery
{
    /**
     * @return Collection<int, PaymentGateway>
     */
    public function execute(): Collection
    {
        return PaymentGateway::query()
            ->where('is_active', true)
            ->get();
    }
}
