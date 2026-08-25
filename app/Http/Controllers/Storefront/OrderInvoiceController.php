<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Models\Order;
use App\Models\User;
use App\Utilities\InvoicePdfGenerator;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\Response;

final readonly class OrderInvoiceController
{
    public function __invoke(int $orderId, #[CurrentUser] User $user): Response
    {
        $order = Order::query()->where('customer_id', $user->id)->findOrFail($orderId);

        return InvoicePdfGenerator::response($order, download: true);
    }
}
