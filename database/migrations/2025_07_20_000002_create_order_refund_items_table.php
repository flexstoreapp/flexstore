<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('order_refund_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_refund_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->foreignId('order_item_id')->nullable()->constrained()->cascadeOnDelete();
            $table->unsignedInteger('quantity')->nullable();
            $table->decimal('amount', 19, 4);
            $table->boolean('restock');
            $table->timestamps();
        });
    }
};
