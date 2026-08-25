<?php

declare(strict_types=1);

use App\Actions\InstallDemoDataAction;
use App\Enums\TransactionType;
use App\Models\Cart;
use App\Models\CheckoutSession;
use App\Models\Media;
use App\Models\Order;
use App\Models\OrderActivity;
use App\Models\OrderItem;
use App\Models\OrderTransaction;
use App\Models\PaymentGateway;
use App\Models\Product;
use App\Models\Setting;
use App\Models\ShippingRate;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

covers(InstallDemoDataAction::class);

uses()->group('demo-data');

/**
 * The dump relies on foreign keys being disabled while it loads, which SQLite
 * only honours outside a transaction — so the demo data goes into its own
 * file-backed connection instead of the transaction-wrapped test database.
 */
beforeEach(function () {
    $this->demoDatabase = sys_get_temp_dir() . '/flexstore-demo-' . bin2hex(random_bytes(6)) . '.sqlite';

    File::put($this->demoDatabase, '');

    config([
        'database.connections.demo' => [
            'driver' => 'sqlite',
            'database' => $this->demoDatabase,
            'prefix' => '',
            'foreign_key_constraints' => true,
        ],
        'database.default' => 'demo',
    ]);

    DB::purge('demo');

    Artisan::call('migrate', ['--database' => 'demo', '--force' => true]);

    Storage::fake('public');
});

afterEach(function () {
    DB::purge('demo');

    File::delete($this->demoDatabase);
});

test('the demo dataset is imported with media, shipping and payment gateways', function () {
    resolve(InstallDemoDataAction::class)->handle();

    expect(Product::query()->count())->toBeGreaterThan(0)
        ->and(Order::query()->count())->toBeGreaterThan(0)
        ->and(ShippingRate::query()->count())->toBeGreaterThan(0)
        ->and(PaymentGateway::query()->count())->toBeGreaterThan(0)
        ->and(Media::query()->whereLike('path', 'images/%')->count())->toBe(0);

    $media = Media::query()->whereNotNull('thumbnail_path')->first();

    Storage::disk('public')->assertExists($media->path);
    Storage::disk('public')->assertExists($media->thumbnail_path);
});

test('the demo store uses the docs favicon', function () {
    resolve(InstallDemoDataAction::class)->handle();

    $favicon = Setting::getValue('store_favicon');
    $media = Media::query()->find(902);

    expect($favicon)->toBeArray()
        ->and($favicon['id'])->toBe(902)
        ->and($favicon['url'])->not->toBeEmpty()
        ->and($media)->not->toBeNull();

    Storage::disk('public')->assertExists($media->path);
});

test('dates are shifted so the newest order is close to today', function () {
    resolve(InstallDemoDataAction::class)->handle();

    expect(now()->diffInDays(Order::query()->max('created_at'), absolute: true))->toBeLessThan(30);
});

test('demo staff keep their documented password on a command line install', function () {
    resolve(InstallDemoDataAction::class)->handle();

    $admin = User::query()->where('email', 'admin@flexstore.app')->first();

    expect(Hash::check('password', $admin->password))->toBeTrue();
});

test('a live store install receives no abandoned checkouts', function () {
    resolve(InstallDemoDataAction::class)->handle(forLiveStore: true);

    expect(CheckoutSession::query()->count())->toBe(0)
        ->and(Cart::query()->count())->toBe(0)
        ->and(DB::table('cart_items')->count())->toBe(0);
});

test('demo abandoned checkout items include product thumbnails', function () {
    resolve(InstallDemoDataAction::class)->handle();

    $sessions = CheckoutSession::query()->get();

    expect($sessions)->not->toBeEmpty();

    foreach ($sessions as $session) {
        expect($session->items)->not->toBeEmpty();

        foreach ($session->items as $item) {
            expect($item['thumbnail_url'] ?? null)->not->toBeEmpty()
                ->and($item['thumbnail_url'])->toContain('/storage/');
        }
    }
});

test('a command line install keeps the orders', function () {
    resolve(InstallDemoDataAction::class)->handle();

    expect(Order::query()->count())->toBeGreaterThan(0)
        ->and(OrderActivity::query()->count())->toBeGreaterThan(0);
});

test('order item variant titles are imported as plain text', function () {
    resolve(InstallDemoDataAction::class)->handle();

    $variantTitles = OrderItem::query()->whereNotNull('variant_title')->pluck('variant_title');

    expect($variantTitles)->not->toBeEmpty()
        ->and($variantTitles->filter(fn (string $title): bool => str_starts_with($title, '{')))->toBeEmpty();
});

test('demo order transactions use valid transaction types', function () {
    resolve(InstallDemoDataAction::class)->handle();

    $transactions = OrderTransaction::query()->get();

    expect($transactions)->not->toBeEmpty()
        ->and($transactions->pluck('type')->unique()->values()->all())
        ->each->toBeInstanceOf(TransactionType::class);
});
