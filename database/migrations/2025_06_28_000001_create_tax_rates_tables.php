<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('tax_rates', function (Blueprint $table): void {
            $table->id();
            $table->jsonb('name');
            $table->string('tax_category')->nullable();
            $table->foreignId('region_id')->constrained()->cascadeOnDelete();
            $table->decimal('rate', 10, 2);
            $table->decimal('min_order_value', 19, 4)->nullable();
            $table->decimal('max_order_value', 19, 4)->nullable();
            $table->boolean('is_compound');
            $table->boolean('is_active');
            $table->unsignedSmallInteger('priority');
            $table->timestamps();
        });
    }
};
