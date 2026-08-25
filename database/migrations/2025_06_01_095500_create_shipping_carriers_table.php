<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('shipping_carriers', function (Blueprint $table): void {
            $table->id();
            $table->jsonb('name');
            $table->string('driver');
            $table->boolean('is_active');
            $table->timestamps();
        });
    }
};
