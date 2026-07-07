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
            $table->foreignId('station_type_id')->constrained('work_station_types')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['part_id', 'station_type_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('part_work_station_types');
    }
};
