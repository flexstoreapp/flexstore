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
        Schema::create('shipping_rates', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('region_id')->constrained()->cascadeOnDelete();
            $table->foreignId('shipping_carrier_id')->constrained()->cascadeOnDelete();
            $table->jsonb('name');
            $table->string('type');
            $table->decimal('rate', 19, 4)->nullable();
            $table->jsonb('delivery_time')->nullable();
            $table->decimal('min_order_value', 19, 4)->nullable();
            $table->decimal('max_order_value', 19, 4)->nullable();
            $table->decimal('min_weight', 10, 2)->nullable();
            $table->string('min_weight_unit')->nullable();
            $table->decimal('max_weight', 10, 2)->nullable();
            $table->string('max_weight_unit')->nullable();
            $table->jsonb('excluded_products')->default(new Expression('(JSON_ARRAY())'));
            $table->jsonb('excluded_categories')->default(new Expression('(JSON_ARRAY())'));
            $table->jsonb('excluded_brands')->default(new Expression('(JSON_ARRAY())'));
            $table->boolean('is_active');
            $table->timestamps();
        });
    }
};
