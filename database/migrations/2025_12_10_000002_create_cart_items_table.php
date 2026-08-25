<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('cart_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignUuid('cart_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('product_variant_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('quantity');
            $table->decimal('unit_price', 19, 4);
            $table->decimal('compare_at_price', 19, 4)->nullable();
            $table->decimal('total_price', 19, 4);
            $table->string('variant_title')->nullable();
            $table->jsonb('variant_options')->nullable();
            $table->timestamps();

            $table->unique(['cart_id', 'product_id', 'product_variant_id']);
        });
    }
};
