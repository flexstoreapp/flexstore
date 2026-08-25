<?php

declare(strict_types=1);

use App\Enums\FulfillmentStatus;
use App\Enums\PaymentStatus;
use App\Enums\Role as RoleEnum;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderShipment;
use App\Models\OrderShipmentItem;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\freezeTime;

pest()->extend(Tests\TestCase::class)
    ->use(LazilyRefreshDatabase::class)
    ->beforeEach(function () {
        Str::createRandomStringsNormally();
        Str::createUuidsNormally();
        Http::preventStrayRequests();
        Sleep::fake();
        Config::set('inertia.ssr.enabled', false);

        freezeTime();
    })
    ->in('Feature', 'Unit');

function passkeyCredentialPayload(string $credentialId = 'login-credential'): array
{
    return [
        'id' => ParagonIE\ConstantTime\Base64UrlSafe::encodeUnpadded($credentialId),
        'rawId' => ParagonIE\ConstantTime\Base64UrlSafe::encodeUnpadded($credentialId),
        'type' => 'public-key',
        'response' => [
            'clientDataJSON' => ParagonIE\ConstantTime\Base64UrlSafe::encodeUnpadded(json_encode([
                'type' => 'webauthn.get',
                'challenge' => ParagonIE\ConstantTime\Base64UrlSafe::encodeUnpadded('challenge'),
                'origin' => 'https://localhost',
            ])),
            'authenticatorData' => ParagonIE\ConstantTime\Base64UrlSafe::encodeUnpadded(str_repeat("\0", 37)),
            'signature' => ParagonIE\ConstantTime\Base64UrlSafe::encodeUnpadded('signature'),
        ],
    ];
}

function actingAsSuperAdmin(): Tests\TestCase
{
    $superAdmin = Role::query()->firstOrCreate(['name' => RoleEnum::SuperAdmin]);
    $user = User::factory()->create()->assignRole($superAdmin);

    return actingAs($user);
}

function actingAsAdmin(): Tests\TestCase
{
    $admin = Role::query()->where(['name' => RoleEnum::Admin])->firstOrFail();

    return actingAs(User::factory()->create()->assignRole($admin));
}

function userWithPermissions(array $permissions): User
{
    $role = Role::query()->create(['name' => 'Scoped ' . fake()->unique()->words(3, true)]);
    $role->givePermissionTo($permissions);

    return User::factory()->create()->assignRole($role);
}

/**
 * A paid, fulfilled order whose single line item has been shipped and delivered,
 * making it eligible for a customer or admin return.
 */
function returnableOrder(?User $customer = null, int $quantity = 2): Order
{
    $order = Order::factory()->create([
        'payment_status' => PaymentStatus::Paid,
        'fulfillment_status' => FulfillmentStatus::Fulfilled,
        'customer_id' => $customer?->id,
    ]);

    $item = OrderItem::factory()->create([
        'order_id' => $order->id,
        'quantity' => $quantity,
    ]);

    $shipment = OrderShipment::factory()->create([
        'order_id' => $order->id,
        'shipped_at' => now(),
        'delivered_at' => now(),
    ]);

    OrderShipmentItem::factory()->create([
        'order_shipment_id' => $shipment->id,
        'order_item_id' => $item->id,
        'quantity' => $quantity,
    ]);

    return $order->load('items');
}

function rotateAppKey(): void
{
    Config::set('app.key', 'base64:' . base64_encode(random_bytes(32)));

    app()->forgetInstance('encrypter');
    Illuminate\Support\Facades\Crypt::clearResolvedInstances();
}

function castAsTranslatableArray(string $field): array
{
    return [app()->getLocale() => $field];
}

function castAsTranslatableJson(string $field): Illuminate\Contracts\Database\Query\Expression
{
    return \Pest\Laravel\castAsJson([app()->getLocale() => $field]);
}

function loggedSql(string $sql): string
{
    return str_replace(['`', '"'], '', $sql);
}

/**
 * @param  array<string, string>  $translations
 */
function installTestLocale(string $locale, array $translations = []): void
{
    if (! str_starts_with($locale, 'zz')) {
        throw new InvalidArgumentException("Scratch locale \"{$locale}\" must start with \"zz\" so parallel workers ignore it.");
    }

    foreach (App\Utilities\Translations::BUNDLES as $bundle) {
        $base = json_decode((string) file_get_contents(lang_path("{$bundle}/en.json")), true);

        file_put_contents(
            lang_path("{$bundle}/{$locale}.json"),
            json_encode([...$base, ...$translations], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        );
    }
}

function removeTestLocale(string $locale): void
{
    foreach (App\Utilities\Translations::BUNDLES as $bundle) {
        @unlink(lang_path("{$bundle}/{$locale}.json"));
    }
}
