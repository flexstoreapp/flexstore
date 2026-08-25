<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table): void {
            $table->id();
            $table->string('url_handle')->unique();
            $table->jsonb('name');
            $table->jsonb('description')->nullable();
            $table->jsonb('seo_title')->nullable();
            $table->jsonb('seo_description')->nullable();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active');
            $table->timestamps();
        });
    }
};
