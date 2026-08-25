<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('payment_gateways', function (Blueprint $table): void {
            $table->id();
            $table->jsonb('name');
            $table->string('driver')->unique();
            $table->text('credentials')->nullable();
            $table->decimal('min_order_value', 19, 4)->nullable();
            $table->decimal('max_order_value', 19, 4)->nullable();
            $table->decimal('min_weight', 10, 2)->nullable();
            $table->string('min_weight_unit')->nullable();
            $table->decimal('max_weight', 10, 2)->nullable();
            $table->string('max_weight_unit')->nullable();
            $table->jsonb('excluded_products')->default(new Expression('(JSON_ARRAY())'));
            $table->jsonb('excluded_categories')->default(new Expression('(JSON_ARRAY())'));
            $table->jsonb('excluded_brands')->default(new Expression('(JSON_ARRAY())'));
            $table->jsonb('allowed_regions')->default(new Expression('(JSON_ARRAY())'));
            $table->jsonb('supported_currencies')->default(new Expression('(JSON_ARRAY())'));
            $table->boolean('sync_external_refunds')->default(false);
            $table->boolean('is_active');
            $table->timestamps();
        });
    }
};
