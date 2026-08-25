<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('regions', function (Blueprint $table): void {
            $table->id();
            $table->jsonb('name');
            $table->jsonb('countries');
            $table->jsonb('states')->nullable();
            $table->jsonb('postal_codes')->nullable();
            $table->boolean('is_active');
            $table->timestamps();
        });
    }
};
