<?php

declare(strict_types=1);

use App\Actions\DeleteCustomerAccountAction;
use App\Models\User;

use function Pest\Laravel\assertDatabaseMissing;

covers(DeleteCustomerAccountAction::class);

uses()->group('actions', 'auth', 'account');

test('deletes the user', function () {
    $user = User::factory()->create();

    app(DeleteCustomerAccountAction::class)->handle($user);

    assertDatabaseMissing('users', ['id' => $user->id]);
});
