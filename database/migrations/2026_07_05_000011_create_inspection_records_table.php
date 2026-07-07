<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inspection_records', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('part_id')->constrained()->restrictOnDelete();
            $table->foreignId('work_station_id')->constrained()->restrictOnDelete();
            $table->string('stage');
            $table->foreignId('checker_id')->constrained('users')->restrictOnDelete();
            $table->dateTime('checked_at');
            $table->string('shift');
            $table->date('production_date');
            $table->timestamps();

            $table->index(['work_station_id', 'production_date', 'shift']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inspection_records');
    }
};
