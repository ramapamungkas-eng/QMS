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
            $table->string('stage'); // InspectionStage enum: start | middle | end
            $table->foreignId('checker_id')->constrained('users')->restrictOnDelete();
            $table->dateTime('checked_at'); // raw submit timestamp, for audit
            $table->string('shift'); // Shift enum: day | night
            $table->date('production_date'); // normalized per shift logic — see App\Support\ShiftResolver
            $table->timestamps();

            $table->index(['work_station_id', 'production_date', 'shift']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inspection_records');
    }
};
