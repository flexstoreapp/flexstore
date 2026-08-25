<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('order_transactions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_refund_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type');
            $table->string('status');
            $table->decimal('amount', 19, 4);
            $table->string('currency_code', 3);
            $table->foreignId('payment_gateway_id')->nullable()->constrained('payment_gateways')->nullOnDelete();
            $table->string('gateway_reference')->nullable()->index();
            $table->string('payment_method')->nullable();
            $table->jsonb('payment_method_details')->nullable();
            $table->text('failure_reason')->nullable();
            $table->foreignUuid('payment_session_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('related_transaction_id')->nullable()->constrained('order_transactions')->nullOnDelete();
            $table->boolean('is_manual_entry')->default(false);
            $table->jsonb('metadata')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'type']);
        });
    }
};
