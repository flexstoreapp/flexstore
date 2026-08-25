<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('payment_sessions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignId('payment_gateway_id')->constrained()->cascadeOnDelete();
            $table->string('owner_type');
            $table->string('owner_id');
            $table->string('purpose');
            $table->string('currency_code', 3);
            $table->decimal('amount', 19, 4);
            $table->string('customer_email')->nullable();
            $table->string('description')->nullable();
            $table->text('return_url');
            $table->text('cancel_url')->nullable();
            $table->text('failure_url')->nullable();
            $table->text('webhook_url')->nullable();
            $table->string('gateway_reference')->nullable()->index();
            $table->jsonb('payload')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->string('status');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['owner_type', 'owner_id', 'purpose']);
            $table->index(['status', 'created_at']);
        });
    }
};
