<?php

declare(strict_types=1);

use App\Enums\PaymentStatus;
use App\Http\Controllers\Storefront\DownloadController;
use App\Models\Order;
use App\Models\OrderItemDownload;
use App\Models\User;
use App\Queries\CustomerDownloadListQuery;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

covers(DownloadController::class, CustomerDownloadListQuery::class);

uses()->group('account', 'downloads');

test('the downloads page requires authentication', function () {
    get(route('account.downloads.index'))
        ->assertRedirect(route('account.login'));
});

test('the downloads page renders only the customer own downloads', function () {
    $user = User::factory()->create();
    OrderItemDownload::factory()->count(2)->create(['customer_id' => $user->id]);
    OrderItemDownload::factory()->create(['customer_id' => User::factory()->create()->id]);

    actingAs($user)
        ->get(route('account.downloads.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('storefront/account/downloads/list')
            ->has('downloads.data', 2));
});

test('the downloads page omits downloads from canceled or refunded orders', function () {
    $user = User::factory()->create();

    OrderItemDownload::factory()->create(['customer_id' => $user->id, 'order_id' => Order::factory()->paid()]);
    OrderItemDownload::factory()->create([
        'customer_id' => $user->id,
        'order_id' => Order::factory()->create(['canceled_at' => now()]),
    ]);
    OrderItemDownload::factory()->create([
        'customer_id' => $user->id,
        'order_id' => Order::factory()->create(['payment_status' => PaymentStatus::Refunded]),
    ]);

    actingAs($user)
        ->get(route('account.downloads.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('downloads.data', 1)
            ->where('downloads.data.0.is_available', true));
});
