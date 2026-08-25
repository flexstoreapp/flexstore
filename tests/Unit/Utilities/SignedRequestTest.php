<?php

declare(strict_types=1);

use App\Utilities\SignedRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

covers(SignedRequest::class);

uses()->group('utilities');

function signedCheckoutUrl(string $session = 'sess-1'): string
{
    return URL::temporarySignedRoute('checkout.success', now()->addDay(), ['session' => $session]);
}

test('a valid signed url passes', function () {
    $request = Request::create(signedCheckoutUrl());

    expect(SignedRequest::hasValidSignature($request, ['session']))->toBeTrue();
});

test('appended gateway params are ignored automatically', function () {
    $request = Request::create(signedCheckoutUrl() . '&tap_id=chg_1&data=BLOB&some_future_param=x');

    expect(SignedRequest::hasValidSignature($request, ['session']))->toBeTrue();
});

test('tampering an own signed param fails', function () {
    $forged = str_replace('session=sess-1', 'session=sess-2', signedCheckoutUrl('sess-1'));

    expect(SignedRequest::hasValidSignature(Request::create($forged), ['session']))->toBeFalse();
});

test('an unsigned url fails', function () {
    $request = Request::create(route('checkout.success', ['session' => 'sess-1']));

    expect(SignedRequest::hasValidSignature($request, ['session']))->toBeFalse();
});
