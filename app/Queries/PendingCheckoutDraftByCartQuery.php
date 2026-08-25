<?php

declare(strict_types=1);

namespace App\Queries;

use App\Enums\CheckoutSessionStatus;
use App\Models\CheckoutSession;

final readonly class PendingCheckoutDraftByCartQuery
{
    /**
     * @return array{customer_email: string, shipping_address: array<string, mixed>|null, billing_address: array<string, mixed>|null, different_billing_address: bool, notes: string|null, customer_address_id: int|null}|null
     */
    public function execute(string $cartId): ?array
    {
        $session = CheckoutSession::query()
            ->where('cart_id', $cartId)
            ->where('status', CheckoutSessionStatus::Pending)
            ->latest()
            ->latest('id')
            ->first(['customer_email', 'shipping_address', 'billing_address', 'different_billing_address', 'notes', 'customer_address_id']);

        if (! $session instanceof CheckoutSession) {
            return null;
        }

        return [
            'customer_email' => $session->customer_email,
            'shipping_address' => $session->shipping_address,
            'billing_address' => $session->billing_address,
            'different_billing_address' => $session->different_billing_address,
            'notes' => $session->notes,
            'customer_address_id' => $session->customer_address_id,
        ];
    }
}
