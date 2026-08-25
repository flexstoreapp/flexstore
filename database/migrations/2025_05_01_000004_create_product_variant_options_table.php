<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('product_variant_options', function (Blueprint $table): void {
            $table->id();
            $table->foreignUuid('product_variant_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('product_option_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('product_option_value_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['product_variant_id', 'product_option_id'], 'product_variant_id_product_option_id_unique');
        });
    }
};
