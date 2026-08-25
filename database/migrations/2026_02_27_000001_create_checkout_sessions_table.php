<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('checkout_sessions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('cart_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('customer_email');
            $table->jsonb('shipping_address')->nullable();
            $table->jsonb('billing_address')->nullable();
            $table->foreignId('customer_address_id')->nullable()->constrained('customer_addresses')->nullOnDelete();
            $table->boolean('different_billing_address')->default(false);
            $table->foreignId('shipping_rate_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('shipping_carrier_id')->nullable()->constrained()->nullOnDelete();
            $table->jsonb('shipping_carrier_name')->nullable();
            $table->jsonb('shipping_rate_name')->nullable();
            $table->foreignId('region_id')->nullable()->constrained()->nullOnDelete();
            $table->jsonb('region_name')->nullable();
            $table->foreignId('payment_gateway_id')->nullable()->constrained()->nullOnDelete();
            $table->jsonb('payment_gateway_name')->nullable();
            $table->foreignId('coupon_id')->nullable()->constrained()->nullOnDelete();
            $table->string('coupon_code')->nullable();
            $table->text('notes')->nullable();
            $table->jsonb('items')->nullable();
            $table->unsignedInteger('total_quantity')->default(0);
            $table->string('currency_code', 3)->nullable();
            $table->decimal('exchange_rate', 19, 4)->default(1);
            $table->boolean('prices_include_tax')->nullable();
            $table->boolean('shipping_is_taxable')->nullable();
            $table->string('tax_based_on')->nullable();
            $table->decimal('default_tax_rate', 8, 4)->nullable();
            $table->string('tax_store_country_code', 2)->nullable();
            $table->string('tax_store_state')->nullable();
            $table->string('tax_store_postal_code')->nullable();
            $table->decimal('subtotal', 19, 4)->default(0);
            $table->decimal('tax_total', 19, 4)->default(0);
            $table->decimal('shipping_total', 19, 4)->default(0);
            $table->decimal('discount_total', 19, 4)->default(0);
            $table->decimal('total', 19, 4)->default(0);
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status');
            $table->string('step')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['cart_id', 'status']);
            $table->index(['status', 'expires_at']);
            $table->index(['status', 'created_at']);
        });
    }
};
