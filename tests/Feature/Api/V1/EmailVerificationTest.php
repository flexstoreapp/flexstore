<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\VerifyEmailController;
use App\Http\Requests\Api\V1\VerifyEmailRequest;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;

use function Pest\Laravel\postJson;

covers(VerifyEmailController::class, VerifyEmailRequest::class);

uses()->group('api');

function verificationHashFor(User $user): string
{
    return hash('sha1', (string) $user->getEmailForVerification());
}

/**
 * The query the emailed link carries, which is what a native client reads out of it.
 *
 * @return array<string, mixed>
 */
function verificationLinkQuery(User $user, ?string $hash = null): array
{
    $hash ??= verificationHashFor($user);

    $link = URL::temporarySignedRoute('account.verification.verify', now()->addHour(), [
        'id' => $user->id,
        'hash' => $hash,
    ]);

    parse_str((string) parse_url($link, PHP_URL_QUERY), $query);

    return [
        'id' => $user->id,
        'hash' => $hash,
        'expires' => (int) $query['expires'],
        'signature' => (string) $query['signature'],
    ];
}

test('the id and hash from the emailed link verify the address', function (): void {
    Event::fake([Verified::class]);
    $user = actingAsApiCustomer(User::factory()->unverified()->create());

    postJson(route('api.v1.auth.verification.verify'), verificationLinkQuery($user))->assertOk();

    expect($user->refresh()->hasVerifiedEmail())->toBeTrue();

    Event::assertDispatched(Verified::class);
});

test('an already verified address is accepted without dispatching the event again', function (): void {
    Event::fake([Verified::class]);
    $user = actingAsApiCustomer(User::factory()->create());

    postJson(route('api.v1.auth.verification.verify'), verificationLinkQuery($user))->assertOk();

    Event::assertNotDispatched(Verified::class);
});

test('a hash from another address is forbidden', function (): void {
    $user = actingAsApiCustomer(User::factory()->unverified()->create());

    postJson(route('api.v1.auth.verification.verify'), verificationLinkQuery($user, hash('sha1', 'someone-else@example.com')))
        ->assertForbidden();

    expect($user->refresh()->hasVerifiedEmail())->toBeFalse();
});

test('a link belonging to another account is forbidden', function (): void {
    $user = actingAsApiCustomer(User::factory()->unverified()->create());
    $other = User::factory()->unverified()->create();

    postJson(route('api.v1.auth.verification.verify'), verificationLinkQuery($other))
        ->assertForbidden();

    expect($other->refresh()->hasVerifiedEmail())->toBeFalse();
});

test('the id and hash are required', function (): void {
    actingAsApiCustomer(User::factory()->unverified()->create());

    postJson(route('api.v1.auth.verification.verify'), [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['id', 'hash', 'expires', 'signature']);
});

test('a guest cannot verify an address', function (): void {
    $user = User::factory()->unverified()->create();

    postJson(route('api.v1.auth.verification.verify'), verificationLinkQuery($user))->assertUnauthorized();
});

test('a forged signature is rejected', function (): void {
    $user = actingAsApiCustomer(User::factory()->unverified()->create());

    postJson(route('api.v1.auth.verification.verify'), [
        ...verificationLinkQuery($user),
        'signature' => str_repeat('a', 64),
    ])->assertForbidden();

    expect($user->refresh()->hasVerifiedEmail())->toBeFalse();
});

test('a link that has expired is rejected', function (): void {
    $user = actingAsApiCustomer(User::factory()->unverified()->create());
    $query = verificationLinkQuery($user);

    $this->travel(2)->hours();

    postJson(route('api.v1.auth.verification.verify'), $query)->assertForbidden();

    expect($user->refresh()->hasVerifiedEmail())->toBeFalse();
});
