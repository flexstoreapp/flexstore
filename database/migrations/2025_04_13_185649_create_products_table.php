<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->string('type', 20)->default('physical')->index();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('tax_category')->nullable();
            $table->foreignId('brand_id')->nullable()->constrained()->nullOnDelete();
            $table->string('url_handle')->unique();
            $table->jsonb('title');
            $table->jsonb('description')->nullable();
            $table->decimal('price', 19, 4)->nullable();
            $table->decimal('compare_at_price', 19, 4)->nullable();
            $table->decimal('cost_per_item', 19, 4)->nullable();
            $table->string('sku', 100)->nullable()->unique();
            $table->string('barcode', 100)->nullable();
            $table->boolean('track_stock')->nullable();
            $table->integer('stock')->nullable();
            $table->unsignedInteger('low_stock_threshold')->nullable();
            $table->boolean('in_stock')->nullable();
            $table->decimal('weight', 10, 2)->nullable();
            $table->string('weight_unit', 10)->nullable();
            $table->decimal('length', 10, 2)->nullable();
            $table->decimal('width', 10, 2)->nullable();
            $table->decimal('height', 10, 2)->nullable();
            $table->string('dimension_unit', 10)->nullable();
            $table->boolean('is_tax_exempt');
            $table->boolean('is_active');
            $table->jsonb('seo_title');
            $table->jsonb('seo_description')->nullable();
            $table->timestamps();
        });
    }
};
