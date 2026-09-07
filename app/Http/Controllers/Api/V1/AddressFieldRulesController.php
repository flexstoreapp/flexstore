<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Address\AddressFieldRules;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

final readonly class AddressFieldRulesController
{
    public function __invoke(string $country): JsonResponse
    {
        $code = mb_strtoupper($country);

        $format = Cache::rememberForever(
            "address-field-rules:{$code}:" . AddressFieldRules::version(),
            static fn (): array => AddressFieldRules::for($code),
        );

        return response()->json(['data' => $format]);
    }
}
