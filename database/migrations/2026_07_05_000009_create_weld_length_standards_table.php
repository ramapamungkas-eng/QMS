<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('weld_length_standards', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('part_id')->constrained()->cascadeOnDelete();
            $table->foreignId('work_station_id')->constrained()->cascadeOnDelete();
            $table->decimal('min_length', 8, 2);
            $table->decimal('max_length', 8, 2);
            $table->string('unit')->default('mm');
            $table->timestamps();

            $table->unique(['part_id', 'work_station_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weld_length_standards');
    }
};
