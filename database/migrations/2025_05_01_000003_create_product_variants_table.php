<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->jsonb('title');
            $table->decimal('price', 19, 4);
            $table->decimal('compare_at_price', 19, 4)->nullable();
            $table->decimal('cost_per_item', 19, 4)->nullable();
            $table->string('sku')->nullable();
            $table->string('barcode')->nullable();
            $table->boolean('track_stock');
            $table->integer('stock')->nullable();
            $table->unsignedInteger('low_stock_threshold')->nullable();
            $table->boolean('in_stock');
            $table->decimal('weight', 10, 2)->nullable();
            $table->string('weight_unit', 10)->nullable();
            $table->decimal('length', 10, 2)->nullable();
            $table->decimal('width', 10, 2)->nullable();
            $table->decimal('height', 10, 2)->nullable();
            $table->string('dimension_unit', 10)->nullable();
            $table->foreignId('media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->unique(['product_id', 'sku']);
            $table->unique(['product_id', 'barcode']);
            $table->index(['product_id', 'is_default']);
        });
    }
};
