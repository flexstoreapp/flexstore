<?php

declare(strict_types=1);

use App\Enums\Currency as CurrencyEnum;
use App\Enums\CurrencySymbolPosition;
use App\Models\Currency;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('currencies', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 3)->unique();
            $table->string('symbol', 10);
            $table->decimal('exchange_rate', 19, 4);
            $table->string('symbol_position', 20)->default(CurrencySymbolPosition::Before->value);
            $table->string('thousands_separator', 1)->default(',');
            $table->string('decimal_separator', 1)->default('.');
            $table->unsignedTinyInteger('decimal_places')->default(2);
            $table->boolean('is_active');
            $table->timestamps();
        });

        Currency::query()->create([
            'code' => CurrencyEnum::USD->name,
            'symbol' => '$',
            'exchange_rate' => 1,
            'symbol_position' => CurrencySymbolPosition::Before->value,
            'thousands_separator' => ',',
            'decimal_separator' => '.',
            'decimal_places' => 2,
            'is_active' => true,
        ]);
    }
};
