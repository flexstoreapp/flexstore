<?php

declare(strict_types=1);

use App\Enums\PaymentStatus;
use App\Enums\TokenAbility;
use App\Http\Controllers\Api\V1\DownloadController;
use App\Models\Order;
use App\Models\OrderItemDownload;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\getJson;

covers(DownloadController::class);

uses()->group('api');

function apiDownloadFor(User $customer, array $overrides = []): OrderItemDownload
{
    Storage::put('downloads/file.zip', 'binary-content');

    return OrderItemDownload::factory()->create([
        'order_id' => Order::factory()->create([
            'payment_status' => PaymentStatus::Paid,
            'customer_id' => $customer->id,
            'canceled_at' => null,
        ])->id,
        'customer_id' => $customer->id,
        'file_path' => 'downloads/file.zip',
        'original_filename' => 'manual.zip',
        ...$overrides,
    ]);
}

beforeEach(function (): void {
    Storage::fake();
});

test('downloads are listed for the authenticated customer', function (): void {
    $user = User::factory()->create();
    $download = apiDownloadFor($user);
    apiDownloadFor(User::factory()->create());

    Sanctum::actingAs($user, [TokenAbility::Customer->value]);

    getJson(route('api.v1.account.downloads.index'))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.token', $download->token);
});

test('a file is streamed to its owner and the download is counted', function (): void {
    $user = User::factory()->create();
    $download = apiDownloadFor($user);

    Sanctum::actingAs($user, [TokenAbility::Customer->value]);

    getJson(route('api.v1.account.downloads.show', $download->token))->assertOk();

    expect($download->refresh()->download_count)->toBe(1);
});

test('another customer cannot fetch the file', function (): void {
    $download = apiDownloadFor(User::factory()->create());

    Sanctum::actingAs(User::factory()->create(), [TokenAbility::Customer->value]);

    getJson(route('api.v1.account.downloads.show', $download->token))->assertForbidden();
});
