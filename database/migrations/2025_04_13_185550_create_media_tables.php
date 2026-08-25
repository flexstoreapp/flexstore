<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('media', function (Blueprint $table): void {
            $table->id();
            $table->string('type');
            $table->string('disk');
            $table->string('path', 500)->nullable();
            $table->string('external_url', 2048)->nullable();
            $table->string('thumbnail_path', 500)->nullable();
            $table->string('small_thumbnail_path', 500)->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->unsignedInteger('duration')->nullable();
            $table->string('original_filename')->nullable();
            $table->string('alt', 1000)->nullable();
            $table->timestamps();
            $table->index('created_at');
        });

        Schema::create('mediables', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('media_id')->constrained('media')->cascadeOnDelete();
            $table->string('mediable_type');
            $table->string('mediable_id', 36);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['media_id', 'mediable_type', 'mediable_id']);
            $table->index(['mediable_type', 'mediable_id']);
        });
    }
};
