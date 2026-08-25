<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
            $table->string('payment_status');
            $table->string('fulfillment_status')->default('unfulfilled');
            $table->foreignId('customer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('customer_email')->index();
            $table->boolean('prices_include_tax');
            $table->boolean('shipping_is_taxable');
            $table->string('tax_based_on');
            $table->decimal('default_tax_rate', 8, 4)->nullable();
            $table->string('tax_store_country_code', 2)->nullable();
            $table->string('tax_store_state')->nullable();
            $table->string('tax_store_postal_code')->nullable();
            $table->decimal('subtotal', 19, 4)->default(0);
            $table->decimal('discount_total', 19, 4)->default(0);
            $table->decimal('shipping_total', 19, 4)->default(0);
            $table->decimal('tax_total', 19, 4)->default(0);
            $table->decimal('total', 19, 4)->default(0);
            $table->decimal('paid_total', 19, 4)->default(0);
            $table->decimal('refund_total', 19, 4)->default(0);
            $table->decimal('net_paid_total', 19, 4)->default(0);
            $table->decimal('balance_due_total', 19, 4)->default(0);
            $table->decimal('credit_due_total', 19, 4)->default(0);
            $table->string('currency_code', 3);
            $table->decimal('exchange_rate', 19, 4)->default(1);
            $table->foreignId('shipping_carrier_id')->nullable()->constrained()->nullOnDelete();
            $table->jsonb('shipping_carrier_name')->nullable();
            $table->foreignId('shipping_rate_id')->nullable()->constrained()->nullOnDelete();
            $table->jsonb('shipping_rate_name')->nullable();
            $table->foreignId('region_id')->nullable()->constrained()->nullOnDelete();
            $table->jsonb('region_name')->nullable();
            $table->foreignId('payment_gateway_id')->nullable()->constrained()->nullOnDelete();
            $table->jsonb('payment_gateway_name')->nullable();
            $table->foreignId('coupon_id')->nullable()->constrained()->nullOnDelete();
            $table->string('coupon_code')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('canceled_at')->nullable();
            $table->string('cancellation_reason')->nullable();
            $table->text('cancellation_note')->nullable();
            $table->timestamps();

            $table->index(['fulfillment_status', 'created_at']);
            $table->index(['customer_email', 'created_at']);
            $table->index(['customer_id', 'created_at']);
            $table->index(['payment_status', 'created_at']);
            $table->index(['canceled_at', 'created_at']);
        });

        $this->setAutoIncrementStart('orders', 10001);
    }

    private function setAutoIncrementStart(string $table, int $start): void
    {
        match (DB::getDriverName()) {
            'mysql', 'mariadb' => DB::statement("ALTER TABLE {$table} AUTO_INCREMENT = {$start}"),
            'pgsql' => DB::statement("ALTER SEQUENCE {$table}_id_seq RESTART WITH {$start}"),
            'sqlite' => $this->setSqliteSequence($table, $start - 1),
            default => null,
        };
    }

    private function setSqliteSequence(string $table, int $seq): void
    {
        DB::statement("INSERT OR IGNORE INTO sqlite_sequence(name, seq) VALUES('{$table}', {$seq})");
        DB::statement("UPDATE sqlite_sequence SET seq = {$seq} WHERE name = '{$table}'");
    }
};
