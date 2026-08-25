<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('menu_items', function (Blueprint $table): void {
            $table->id();
            $table->string('location');
            $table->jsonb('label');
            $table->string('link_type');
            $table->foreignId('brand_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('featured_image_id')->nullable()->constrained('media')->nullOnDelete();
            $table->jsonb('featured_title')->nullable();
            $table->string('featured_url')->nullable();
            $table->string('url')->nullable();
            $table->string('page')->nullable();
            $table->string('target');
            $table->foreignId('parent_id')->nullable()->constrained('menu_items')->nullOnDelete();
            $table->unsignedInteger('sort_order');
            $table->boolean('is_mega_menu')->default(false);
            $table->boolean('is_active');
            $table->timestamps();

            $table->index(['location', 'parent_id', 'sort_order']);
            $table->index('is_active');
        });
    }
};
