<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brands', function (Blueprint $table): void {
            $table->id();
            $table->jsonb('name');
            $table->string('url_handle')->unique();
            $table->jsonb('description')->nullable();
            $table->jsonb('seo_title')->nullable();
            $table->jsonb('seo_description')->nullable();
            $table->foreignId('image_id')->nullable()->constrained('media')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }
};
