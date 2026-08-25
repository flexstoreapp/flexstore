<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Models\Order;
use App\Utilities\InvoicePdfGenerator;
use Illuminate\Http\Response;

final readonly class OrderInvoiceController
{
    public function __invoke(Order $order): Response
    {
        return InvoicePdfGenerator::response($order);
    }
}
