<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\CalculateOrderTaxRequest;
use App\Models\Currency;
use App\Utilities\OrderTaxCalculator;
use Illuminate\Http\JsonResponse;

final readonly class OrderTaxCalculatorController
{
    public function __invoke(
        CalculateOrderTaxRequest $request,
        OrderTaxCalculator $orderTaxCalculator,
    ): JsonResponse {
        $decimalPlaces = Currency::getDecimalPlaces($request->getCurrencyCode());

        $result = $orderTaxCalculator
            ->calculate($request->toDto())
            ->scaledTo($decimalPlaces);

        return response()->json([
            'tax_total' => $result->taxTotal,
            'tax_details' => $result->aggregatedTaxDetails(),
        ]);
    }
}
