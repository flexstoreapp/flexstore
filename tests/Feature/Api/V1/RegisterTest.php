<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Http\Controllers\Api\V1\RegisterController;
use App\Http\Requests\Api\V1\StoreRegistrationRequest;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Event;

use function Pest\Laravel\postJson;

covers(RegisterController::class, StoreRegistrationRequest::class);

uses()->group('api');

test('a customer can register and receive an access token', function (): void {
    Event::fake([Registered::class]);

    $response = postJson(route('api.v1.auth.register'), [
        'name' => 'Ada Lovelace',
        'email' => 'ada@example.com',
        'password' => 'password-1234',
        'password_confirmation' => 'password-1234',
        'device_name' => 'iPhone 15',
    ]);

    $response->assertCreated()
        ->assertJsonStructure(['token', 'user' => ['id', 'name', 'email', 'email_verified', 'created_at']])
        ->assertJsonPath('user.email', 'ada@example.com');

    $user = User::query()->where('email', 'ada@example.com')->sole();

    expect($user->hasRole(Role::Customer))->toBeTrue()
        ->and($user->tokens()->where('name', 'iPhone 15')->exists())->toBeTrue();

    Event::assertDispatched(Registered::class);
});

test('registration requires a device name', function (): void {
    postJson(route('api.v1.auth.register'), [
        'name' => 'Ada Lovelace',
        'email' => 'ada@example.com',
        'password' => 'password-1234',
        'password_confirmation' => 'password-1234',
    ])->assertJsonValidationErrors('device_name');
});

test('registration rejects a duplicate email', function (): void {
    User::factory()->create(['email' => 'ada@example.com']);

    postJson(route('api.v1.auth.register'), [
        'name' => 'Ada Lovelace',
        'email' => 'ada@example.com',
        'password' => 'password-1234',
        'password_confirmation' => 'password-1234',
        'device_name' => 'iPhone 15',
    ])->assertJsonValidationErrors('email');
});
