<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\VerifyEmailController;
use App\Http\Requests\Api\V1\VerifyEmailRequest;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Event;

use function Pest\Laravel\postJson;

covers(VerifyEmailController::class, VerifyEmailRequest::class);

uses()->group('api');

function verificationHashFor(User $user): string
{
    return hash('sha1', (string) $user->getEmailForVerification());
}

test('the id and hash from the emailed link verify the address', function (): void {
    Event::fake([Verified::class]);
    $user = actingAsApiCustomer(User::factory()->unverified()->create());

    postJson(route('api.v1.auth.verification.verify'), [
        'id' => $user->id,
        'hash' => verificationHashFor($user),
    ])->assertOk();

    expect($user->refresh()->hasVerifiedEmail())->toBeTrue();

    Event::assertDispatched(Verified::class);
});

test('an already verified address is accepted without dispatching the event again', function (): void {
    Event::fake([Verified::class]);
    $user = actingAsApiCustomer(User::factory()->create());

    postJson(route('api.v1.auth.verification.verify'), [
        'id' => $user->id,
        'hash' => verificationHashFor($user),
    ])->assertOk();

    Event::assertNotDispatched(Verified::class);
});

test('a hash from another address is forbidden', function (): void {
    $user = actingAsApiCustomer(User::factory()->unverified()->create());

    postJson(route('api.v1.auth.verification.verify'), [
        'id' => $user->id,
        'hash' => hash('sha1', 'someone-else@example.com'),
    ])->assertForbidden();

    expect($user->refresh()->hasVerifiedEmail())->toBeFalse();
});

test('a link belonging to another account is forbidden', function (): void {
    $user = actingAsApiCustomer(User::factory()->unverified()->create());
    $other = User::factory()->unverified()->create();

    postJson(route('api.v1.auth.verification.verify'), [
        'id' => $other->id,
        'hash' => verificationHashFor($other),
    ])->assertForbidden();

    expect($other->refresh()->hasVerifiedEmail())->toBeFalse();
});

test('the id and hash are required', function (): void {
    actingAsApiCustomer(User::factory()->unverified()->create());

    postJson(route('api.v1.auth.verification.verify'), [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['id', 'hash']);
});

test('a guest cannot verify an address', function (): void {
    $user = User::factory()->unverified()->create();

    postJson(route('api.v1.auth.verification.verify'), [
        'id' => $user->id,
        'hash' => verificationHashFor($user),
    ])->assertUnauthorized();
});
