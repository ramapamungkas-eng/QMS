<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('part_hardware_mappings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('part_id')->constrained()->cascadeOnDelete();
            $table->foreignId('hardware_type_id')->constrained()->cascadeOnDelete();
            $table->string('measurement_type');
            $table->unsignedTinyInteger('usage_qty')->default(1);
            $table->timestamps();

            $table->unique(['part_id', 'hardware_type_id', 'measurement_type'], 'part_hardware_measurement_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('part_hardware_mappings');
    }
};
