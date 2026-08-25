<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Address\AddressFieldRules;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

final readonly class AddressFieldRulesController
{
    public function show(Request $request, string $country): JsonResponse
    {
        $code = mb_strtoupper($country);

        $format = Cache::rememberForever(
            "address-field-rules:{$code}:" . AddressFieldRules::version(),
            static fn (): array => AddressFieldRules::for($code),
        );

        $etag = '"' . hash('xxh128', (string) json_encode($format)) . '"';

        if ($request->headers->get('If-None-Match') === $etag) {
            return response()->json(null, 304)->setEtag(mb_trim($etag, '"'));
        }

        return response()
            ->json($format)
            ->setEtag(mb_trim($etag, '"'))
            ->setPublic()
            ->setMaxAge(86400);
    }
}
