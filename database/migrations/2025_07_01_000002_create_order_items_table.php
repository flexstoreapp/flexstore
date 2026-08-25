<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUuid('product_variant_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->jsonb('product_title');
            $table->string('product_sku')->nullable();
            $table->string('variant_title')->nullable();
            $table->jsonb('variant_options')->nullable();
            $table->unsignedInteger('quantity');
            $table->decimal('unit_price', 19, 4);
            $table->decimal('total_price', 19, 4);
            $table->decimal('cost_per_item', 19, 4)->nullable();
            $table->decimal('tax_amount', 19, 4)->default(0);
            $table->decimal('weight', 10, 2)->nullable();
            $table->string('weight_unit', 10)->nullable();
            $table->decimal('length', 10, 2)->nullable();
            $table->decimal('width', 10, 2)->nullable();
            $table->decimal('height', 10, 2)->nullable();
            $table->string('dimension_unit', 10)->nullable();
            $table->boolean('requires_shipping')->default(true);
            $table->timestamps();

            $table->index(['order_id', 'product_id']);
            $table->index(['product_id', 'order_id']);
        });
    }
};
