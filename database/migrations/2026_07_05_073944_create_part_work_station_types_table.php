<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('part_work_station_types', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('part_id')->constrained()->cascadeOnDelete();
            $table->string('work_station_type');
            $table->timestamps();

            $table->unique(['part_id', 'work_station_type']);
        });

        Schema::dropIfExists('part_process');
    }

    public function down(): void
    {
        Schema::create('part_process', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('part_id')->constrained()->cascadeOnDelete();
            $table->foreignId('process_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['part_id', 'process_id']);
        });

        Schema::dropIfExists('part_work_station_types');
    }
};
