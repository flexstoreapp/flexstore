<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Http\Controllers\Api\V1\Admin\StoreConfigController;
use App\Models\Setting;
use App\Queries\AdminStoreConfigQuery;

use function Pest\Laravel\getJson;

covers(StoreConfigController::class, AdminStoreConfigQuery::class);

uses()->group('api', 'admin-api');

test('the config endpoint returns what an admin client needs to start up', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::DashboardView]));

    getJson(route('api.v1.admin.config'))
        ->assertOk()
        ->assertJsonStructure([
            'store' => ['name', 'email', 'phone', 'country_code', 'logo_url'],
            'locales' => ['default', 'available'],
            'currencies' => ['base', 'available'],
        ])
        ->assertJsonPath('locales.default', 'en')
        ->assertJsonMissingPath('tax');
});

test('the config lists inactive currencies too, because admin edits them', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::DashboardView]));

    $codes = collect(getJson(route('api.v1.admin.config'))->json('currencies.available'));

    expect($codes)->not->toBeEmpty()
        ->and($codes->first())->toHaveKeys(['code', 'symbol', 'decimal_places', 'exchange_rate', 'is_active']);
});

test('the config is reachable while the storefront is in maintenance mode', function (): void {
    Setting::query()->updateOrCreate(['key' => 'maintenance_mode'], ['value' => true]);

    actingAsApiAdmin(userWithPermissions([Permission::DashboardView]));

    getJson(route('api.v1.admin.config'))->assertOk();
    getJson(route('api.v1.config'))->assertServiceUnavailable();
});

test('a customer token cannot read the admin config', function (): void {
    actingAsApiCustomer();

    getJson(route('api.v1.admin.config'))->assertForbidden();
});

test('the admin config needs a token', function (): void {
    getJson(route('api.v1.admin.config'))->assertUnauthorized();
});
