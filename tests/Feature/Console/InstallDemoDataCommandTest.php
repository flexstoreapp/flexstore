<?php

declare(strict_types=1);

use App\Console\Commands\InstallDemoDataCommand;
use App\Enums\SettingType;
use App\Installer\Contracts\InstallationState;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

use function Pest\Laravel\artisan;

covers(InstallDemoDataCommand::class);

uses()->group('console', 'demo-data');

/**
 * The dump relies on foreign keys being disabled while it loads, which SQLite
 * only honours outside a transaction — so the demo data goes into its own
 * file-backed connection instead of the transaction-wrapped test database.
 */
/**
 * @var list<string>
 */
const BOOTSTRAP_CACHE_ENV_KEYS = [
    'APP_PACKAGES_CACHE',
    'APP_SERVICES_CACHE',
    'APP_EVENTS_CACHE',
    'APP_CONFIG_CACHE',
    'APP_ROUTES_CACHE',
];

beforeEach(function () {
    // The command runs optimize:clear, which deletes the bootstrap caches. Those
    // are shared by every parallel worker, so point them at a private directory.
    $this->bootstrapCache = sys_get_temp_dir() . '/flexstore-bootstrap-cache-' . bin2hex(random_bytes(6));

    File::ensureDirectoryExists($this->bootstrapCache);

    // Laravel only treats a cache path as absolute when it starts with a slash,
    // so a Windows temp path like C:\... would be resolved against the base path.
    $drive = mb_substr($this->bootstrapCache, 0, 2);

    if (preg_match('/^[A-Za-z]:$/', $drive) === 1) {
        app()->addAbsoluteCachePathPrefix($drive);
    }

    foreach (BOOTSTRAP_CACHE_ENV_KEYS as $key) {
        $file = mb_strtolower(str_replace(['APP_', '_CACHE'], '', $key)) . '.php';
        $_ENV[$key] = $_SERVER[$key] = $this->bootstrapCache . '/' . $file;
    }

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

    $this->state = Mockery::mock(InstallationState::class);
    app()->instance(InstallationState::class, $this->state);
});

afterEach(function () {
    $this->app['env'] = 'testing';

    DB::purge('demo');

    File::delete($this->demoDatabase);

    File::deleteDirectory($this->bootstrapCache);

    foreach (BOOTSTRAP_CACHE_ENV_KEYS as $key) {
        unset($_ENV[$key], $_SERVER[$key]);
    }
});

/**
 * @return callable(): bool
 */
function spyOnOptimizeCall(): callable
{
    $kernel = app(Kernel::class);
    $optimizeCalled = false;

    Artisan::shouldReceive('call')
        ->andReturnUsing(function (string $command, array $parameters = [], mixed $outputBuffer = null) use ($kernel, &$optimizeCalled): int {
            if ($command === 'optimize') {
                $optimizeCalled = true;

                return 0;
            }

            return $kernel->call($command, $parameters, $outputBuffer);
        });

    return function () use (&$optimizeCalled): bool {
        return $optimizeCalled;
    };
}

test('optimization caches are cleared before confirmation', function () {
    $config = app()->getCachedConfigPath();
    $routes = app()->getCachedRoutesPath();

    expect($config)->toStartWith($this->bootstrapCache)->and($routes)->toStartWith($this->bootstrapCache);

    File::put($config, '<?php return [];');
    File::put($routes, '<?php return [];');

    $this->state->shouldReceive('isInstalled')->andReturn(true);
    $this->state->shouldNotReceive('markAsInstalled');

    try {
        artisan(InstallDemoDataCommand::class)
            ->expectsConfirmation('This app is already installed. Reset the database and reinstall? This will DROP all tables.', 'no')
            ->assertFailed();

        expect(File::exists($config))->toBeFalse()
            ->and(File::exists($routes))->toBeFalse();
    } finally {
        File::delete([$config, $routes]);
    }
});

test('an already installed app must confirm before anything is touched', function () {
    $this->state->shouldReceive('isInstalled')->andReturn(true);
    $this->state->shouldNotReceive('markAsInstalled');

    artisan(InstallDemoDataCommand::class)
        ->expectsConfirmation('This app is already installed. Reset the database and reinstall? This will DROP all tables.', 'no')
        ->assertFailed();

    expect(Product::query()->count())->toBe(0);
});

test('--keep-database installs the dataset without dropping the schema', function () {
    $this->state->shouldReceive('markAsInstalled')->once();

    Setting::query()->create([
        'group' => 'general',
        'key' => 'kept_across_demo_install',
        'value' => 'yes',
        'type' => SettingType::Text,
    ]);

    artisan(InstallDemoDataCommand::class, ['--keep-database' => true])->assertSuccessful();

    expect(Product::query()->count())->toBeGreaterThan(0)
        ->and(Setting::query()->where('key', 'kept_across_demo_install')->exists())->toBeTrue();
});

test('seeded uuid columns hold valid uuids', function () {
    $this->state->shouldReceive('markAsInstalled')->once();

    artisan(InstallDemoDataCommand::class, ['--keep-database' => true])->assertSuccessful();

    $ids = collect([
        'product_options' => ['id'],
        'product_option_values' => ['id', 'product_option_id'],
        'product_variants' => ['id'],
        'product_variant_options' => ['product_variant_id', 'product_option_id', 'product_option_value_id'],
        'wishlists' => ['id'],
        'wishlist_items' => ['wishlist_id'],
    ])->flatMap(fn (array $columns, string $table) => collect($columns)
        ->flatMap(fn (string $column) => DB::table($table)->pluck($column)->all()));

    expect($ids)->not->toBeEmpty()
        ->and($ids->reject(fn ($id) => Str::isUuid((string) $id))->all())->toBe([]);
});

test('optimize is run after install only in production', function (string $env, bool $shouldOptimize) {
    $this->app->instance('env', $env);
    $this->state->shouldReceive('markAsInstalled')->once();

    $optimizeWasCalled = spyOnOptimizeCall();

    artisan(InstallDemoDataCommand::class, ['--keep-database' => true])->assertSuccessful();

    expect($optimizeWasCalled())->toBe($shouldOptimize);
})->with([
    'production' => ['production', true],
    'testing' => ['testing', false],
]);
