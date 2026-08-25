<?php

declare(strict_types=1);

use App\Enums\PaymentStatus;
use App\Http\Controllers\Storefront\DownloadController;
use App\Models\Order;
use App\Models\OrderItemDownload;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

covers(DownloadController::class);

uses()->group('downloads');

beforeEach(function () {
    Storage::fake();
});

function makeGrant(array $overrides = []): OrderItemDownload
{
    $filePath = $overrides['file_path'] ?? 'downloads/file.zip';

    if (! array_key_exists('skip_file', $overrides)) {
        Storage::put($filePath, 'binary-content');
    }
    unset($overrides['skip_file']);

    if (! array_key_exists('order_id', $overrides)) {
        $overrides['order_id'] = Order::factory()->create([
            'payment_status' => PaymentStatus::Paid,
            'canceled_at' => null,
        ])->id;
    }

    return OrderItemDownload::factory()->create([
        'file_path' => $filePath,
        'original_filename' => 'manual.zip',
        ...$overrides,
    ]);
}

test('the authenticated owner can download the file', function () {
    $user = User::factory()->create();
    $grant = makeGrant(['customer_id' => $user->id]);

    $response = actingAs($user)->get(route('downloads.show', ['download' => $grant->token]));

    $response->assertOk()
        ->assertHeader('content-disposition', 'attachment; filename=manual.zip');

    $grant->refresh();
    expect($grant->download_count)->toBe(1)
        ->and($grant->last_downloaded_at)->not->toBeNull();
});

test('a signed url works for a guest grant', function () {
    $grant = makeGrant(['customer_id' => null]);

    $url = URL::signedRoute('downloads.show', ['download' => $grant->token]);

    get($url)->assertOk();

    expect($grant->refresh()->download_count)->toBe(1);
});

test('an unauthenticated unsigned request is forbidden', function () {
    $grant = makeGrant(['customer_id' => User::factory()->create()->id]);

    get(route('downloads.show', ['download' => $grant->token]))
        ->assertForbidden();

    expect($grant->refresh()->download_count)->toBe(0);
});

test('a non-owner authenticated user is forbidden', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $grant = makeGrant(['customer_id' => $owner->id]);

    actingAs($other)
        ->get(route('downloads.show', ['download' => $grant->token]))
        ->assertForbidden();
});

test('an unknown token returns 404', function () {
    $user = User::factory()->create();

    actingAs($user)
        ->get(route('downloads.show', ['download' => 'nonexistent-token']))
        ->assertNotFound();
});

test('a missing file on disk returns 404', function () {
    $user = User::factory()->create();
    $grant = makeGrant([
        'customer_id' => $user->id,
        'file_path' => 'downloads/missing.zip',
        'skip_file' => true,
    ]);

    actingAs($user)
        ->get(route('downloads.show', ['download' => $grant->token]))
        ->assertNotFound();
});

test('a download for a refunded order is no longer available', function () {
    $user = User::factory()->create();
    $order = Order::factory()->create(['payment_status' => PaymentStatus::Refunded]);
    $grant = makeGrant(['customer_id' => $user->id, 'order_id' => $order->id]);

    actingAs($user)
        ->get(route('downloads.show', ['download' => $grant->token]))
        ->assertForbidden();
});

test('a download for a canceled order is no longer available', function () {
    $user = User::factory()->create();
    $order = Order::factory()->create([
        'payment_status' => PaymentStatus::Paid,
        'canceled_at' => now(),
    ]);
    $grant = makeGrant(['customer_id' => $user->id, 'order_id' => $order->id]);

    actingAs($user)
        ->get(route('downloads.show', ['download' => $grant->token]))
        ->assertForbidden();
});
