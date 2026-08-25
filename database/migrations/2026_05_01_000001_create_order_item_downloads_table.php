<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('order_item_downloads', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_item_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('product_download_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('token', 64)->unique();
            $table->string('name');
            $table->string('file_path');
            $table->string('original_filename');
            $table->unsignedBigInteger('file_size');
            $table->string('mime_type')->nullable();
            $table->unsignedInteger('download_count')->default(0);
            $table->timestamp('last_downloaded_at')->nullable();
            $table->timestamp('created_at');

            $table->unique(['order_item_id', 'product_download_id']);
            $table->index(['order_id', 'order_item_id']);
            $table->index('customer_id');
        });
    }
};
