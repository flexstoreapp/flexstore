<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('order_refunds', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('status');
            $table->decimal('amount', 19, 4);
            $table->boolean('is_manual_total')->default(false);
            $table->text('reason')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'status']);
        });

        $this->setAutoIncrementStart('order_refunds', 10001);
    }

    private function setAutoIncrementStart(string $table, int $start): void
    {
        match (DB::getDriverName()) {
            'mysql', 'mariadb' => DB::statement("ALTER TABLE {$table} AUTO_INCREMENT = {$start}"),
            'pgsql' => DB::statement("ALTER SEQUENCE {$table}_id_seq RESTART WITH {$start}"),
            'sqlite' => $this->setSqliteSequence($table, $start - 1),
            default => null,
        };
    }

    private function setSqliteSequence(string $table, int $seq): void
    {
        DB::statement("INSERT OR IGNORE INTO sqlite_sequence(name, seq) VALUES('{$table}', {$seq})");
        DB::statement("UPDATE sqlite_sequence SET seq = {$seq} WHERE name = '{$table}'");
    }
};
