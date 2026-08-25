<?php

declare(strict_types=1);

use App\Http\Controllers\Installer\CompletionController;
use App\Installer\Contracts\InstallationState;

use function Pest\Laravel\get;

covers(CompletionController::class);

uses()->group('installer');

function fakeInstallationState(bool $installed): void
{
    $state = Mockery::mock(InstallationState::class);
    $state->shouldReceive('isInstalled')->andReturn($installed);
    app()->instance(InstallationState::class, $state);
}

test('the completion page offers the storefront and the admin panel once installed', function () {
    fakeInstallationState(true);

    get(route('installer.completion.show'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('installer/done')
            ->where('storefrontUrl', url('/'))
            ->where('adminUrl', route('admin.login'))
        );
});

test('the completion page cannot be visited before the store is installed', function () {
    fakeInstallationState(false);

    get(route('installer.completion.show'))->assertRedirect(route('installer.requirements.show'));
});
