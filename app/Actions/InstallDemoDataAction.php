<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\Role;
use App\Models\Cart;
use App\Models\CheckoutSession;
use App\Models\Coupon;
use App\Models\Media;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Database\Seeders\PaymentGatewaySeeder;
use Database\Seeders\ShippingSeeder;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

final readonly class InstallDemoDataAction
{
    private const string ANCHOR_DATE = '2026-04-15';

    public function handle(bool $rebuildMediaCache = false, bool $forLiveStore = false): void
    {
        $this->importSql();

        $this->randomizePasswords([Role::Customer]);

        if ($forLiveStore) {
            $this->removeOrders();
            $this->removeTrackedActivity();
        }

        Artisan::call('db:seed', ['--class' => ShippingSeeder::class, '--force' => true]);
        Artisan::call('db:seed', ['--class' => PaymentGatewaySeeder::class, '--force' => true]);

        Artisan::call('demo:import-media', array_filter([
            '--source' => $this->path(),
            '--prune' => true,
            '--build' => $rebuildMediaCache,
        ]));

        $this->hydrateAbandonedCheckoutItems();
    }

    private function hydrateAbandonedCheckoutItems(): void
    {
        $sessions = CheckoutSession::query()->get();

        if ($sessions->isEmpty()) {
            return;
        }

        $items = $sessions->flatMap(fn (CheckoutSession $session): array => $session->items ?? []);
        $productIds = $items->pluck('product_id')->filter()->unique()->values()->all();
        $variantIds = $items->pluck('product_variant_id')->filter()->unique()->values()->all();

        $products = $productIds === []
            ? collect()
            : Product::query()
                ->whereIn('id', $productIds)
                ->with([
                    'mediaGallery' => fn (Relation $relation): Relation => $relation
                        ->select(Media::displayColumns())
                        ->limit(1),
                ])
                ->get()
                ->keyBy('id');

        $variants = $variantIds === []
            ? collect()
            : ProductVariant::query()
                ->whereIn('id', $variantIds)
                ->with(['media:' . Media::displaySelect()])
                ->get()
                ->keyBy('id');

        foreach ($sessions as $session) {
            $snapshot = $session->items ?? [];
            $changed = false;

            foreach ($snapshot as $index => $item) {
                $variant = $variants->get($item['product_variant_id'] ?? null);
                $product = $products->get($item['product_id'] ?? null);
                $title = $product?->getTranslations('title');
                $thumbnail = $variant?->media->small_thumbnail_url
                    ?? $product?->featured_media->small_thumbnail_url;

                if ($title !== null && ($item['product_title'] ?? null) !== $title) {
                    $snapshot[$index]['product_title'] = $title;
                    $changed = true;
                }

                if ($thumbnail !== null && ($item['thumbnail_url'] ?? null) !== $thumbnail) {
                    $snapshot[$index]['thumbnail_url'] = $thumbnail;
                    $changed = true;
                }
            }

            if ($changed) {
                $session->forceFill(['items' => $snapshot])->save();
            }
        }
    }

    private function removeOrders(): void
    {
        Order::query()->delete();

        Coupon::query()->where('used_count', '>', 0)->update(['used_count' => 0]);
    }

    private function removeTrackedActivity(): void
    {
        Cart::query()->delete();
        CheckoutSession::query()->delete();
    }

    /**
     * @param  array<int, Role>  $roles
     */
    private function randomizePasswords(array $roles): void
    {
        User::query()
            ->role($roles)
            ->each(function (User $user): void {
                $user->forceFill(['password' => Str::password(32)])->save();
            });
    }

    private function path(): string
    {
        return resource_path('demo');
    }

    private function importSql(): void
    {
        $file = $this->path() . '/demo-data.sql';

        if (! is_file($file)) {
            throw new RuntimeException("Demo data file not found: {$file}");
        }

        $sql = file_get_contents($file);

        if ($sql === false) {
            throw new RuntimeException("Could not read demo data file: {$file}");
        }

        $connection = DB::connection();
        $driver = $connection->getDriverName();
        $pdo = $connection->getPdo();

        $pdo->exec($this->disableConstraints($driver));

        try {
            $pdo->exec($this->shiftDates($sql));
        } finally {
            $pdo->exec($this->enableConstraints($driver));
        }

        if ($driver === 'pgsql') {
            $pdo->exec($this->resetSequences());
        }
    }

    private function disableConstraints(string $driver): string
    {
        return match ($driver) {
            'sqlite' => 'PRAGMA foreign_keys = OFF;',
            'mysql', 'mariadb' => "SET FOREIGN_KEY_CHECKS = 0; SET sql_mode = 'NO_BACKSLASH_ESCAPES';",
            'pgsql' => "SET session_replication_role = 'replica';",
            default => throw new RuntimeException("Demo data cannot be installed on the {$driver} driver."),
        };
    }

    private function enableConstraints(string $driver): string
    {
        return match ($driver) {
            'sqlite' => 'PRAGMA foreign_keys = ON;',
            'mysql', 'mariadb' => 'SET FOREIGN_KEY_CHECKS = 1;',
            'pgsql' => "SET session_replication_role = 'origin';",
            default => throw new RuntimeException("Demo data cannot be installed on the {$driver} driver."),
        };
    }

    private function resetSequences(): string
    {
        return <<<'SQL'
            DO $$
            DECLARE
                r RECORD;
                max_id BIGINT;
            BEGIN
                FOR r IN
                    SELECT
                        c.table_name,
                        c.column_name,
                        pg_get_serial_sequence(c.table_name, c.column_name) AS seq
                    FROM information_schema.columns c
                    WHERE c.table_schema = 'public'
                      AND pg_get_serial_sequence(c.table_name, c.column_name) IS NOT NULL
                LOOP
                    EXECUTE format('SELECT COALESCE(MAX(%I), 0) FROM %I', r.column_name, r.table_name) INTO max_id;
                    IF max_id > 0 THEN
                        EXECUTE format('SELECT setval(%L, %s)', r.seq, max_id);
                    END IF;
                END LOOP;
            END $$;
            SQL;
    }

    private function shiftDates(string $sql): string
    {
        $utc = new DateTimeZone('UTC');
        $anchor = new DateTimeImmutable(self::ANCHOR_DATE, $utc)->setTime(0, 0);
        $today = new DateTimeImmutable('today', $utc)->setTime(0, 0);
        $difference = $anchor->diff($today);
        $offsetDays = (int) $difference->days * ($difference->invert === 1 ? -1 : 1);

        if ($offsetDays === 0) {
            return $sql;
        }

        $modifier = sprintf('%s%d days', $offsetDays >= 0 ? '+' : '-', abs($offsetDays));

        $shifted = preg_replace_callback(
            "/'(\d{4}-\d{2}-\d{2})( \d{2}:\d{2}:\d{2})?'/",
            function (array $match) use ($modifier): string {
                $datetime = new DateTimeImmutable($match[1] . ($match[2] ?? ' 00:00:00'));

                return "'" . $datetime->modify($modifier)->format(isset($match[2]) ? 'Y-m-d H:i:s' : 'Y-m-d') . "'";
            },
            $sql,
        );

        if ($shifted === null) {
            throw new RuntimeException('Failed to shift the demo data dates.');
        }

        return $shifted;
    }
}
