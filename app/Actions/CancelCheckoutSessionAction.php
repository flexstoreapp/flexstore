<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\CheckoutSessionStatus;
use App\Models\CheckoutSession;
use Illuminate\Support\Facades\DB;

final readonly class CancelCheckoutSessionAction
{
    public function __construct(
        private ReleaseStockReservationsAction $releaseStockReservationsAction,
    ) {
    }

    public function handle(CheckoutSession $session): void
    {
        DB::transaction(function () use ($session): void {
            $session = CheckoutSession::query()->lockForUpdate()->findOrFail($session->id);

            if ($session->status !== CheckoutSessionStatus::Pending) {
                return;
            }

            $this->releaseStockReservationsAction->handle($session);

            $session->update(['status' => CheckoutSessionStatus::Canceled]);
        });
    }
}
