<?php

declare(strict_types=1);

namespace App\Utilities;

use Illuminate\Http\Request;

final readonly class SignedRequest
{
    /**
     * @param  list<string>  $ownParams
     */
    public static function hasValidSignature(Request $request, array $ownParams = []): bool
    {
        $reserved = [...$ownParams, 'expires', 'signature'];
        $ignore = array_values(array_diff(array_keys($request->query()), $reserved));

        return $request->hasValidSignatureWhileIgnoring($ignore);
    }
}
