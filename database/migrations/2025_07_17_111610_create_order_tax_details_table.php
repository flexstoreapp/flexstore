<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('order_tax_details', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_item_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('tax_rate_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('item_type')->default('product');
            $table->jsonb('tax_name');
            $table->decimal('tax_rate', 10, 2);
            $table->decimal('taxable_amount', 19, 4);
            $table->decimal('tax_amount', 19, 4);
            $table->decimal('proportion', 6, 4)->nullable();
            $table->boolean('is_compound')->default(false);
            $table->timestamps();
        });
    }
};
