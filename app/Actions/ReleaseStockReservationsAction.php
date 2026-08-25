<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\CheckoutSession;

final readonly class ReleaseStockReservationsAction
{
    public function handle(CheckoutSession $session): void
    {
        $session->reservations()->delete();
    }
}
