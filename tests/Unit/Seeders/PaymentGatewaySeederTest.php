<?php

declare(strict_types=1);

use App\Enums\PaymentGatewayDriver;
use App\Models\PaymentGateway;
use Database\Seeders\PaymentGatewaySeeder;

covers(PaymentGatewaySeeder::class);

uses()->group('seeders', 'demo-data');

test('cash on delivery is always created', function () {
    new PaymentGatewaySeeder()->run();

    $gateway = PaymentGateway::query()->where('driver', PaymentGatewayDriver::Cod)->first();

    expect($gateway)->not->toBeNull()
        ->and($gateway->name)->toBe('Cash on Delivery')
        ->and($gateway->is_active)->toBeTrue()
        ->and($gateway->sync_external_refunds)->toBeTrue();
});

test('does not call factories or faker', function () {
    $source = file_get_contents(database_path('seeders/PaymentGatewaySeeder.php'));

    expect($source)->not->toContain('::factory(')
        ->and($source)->not->toContain('fake(');
});
