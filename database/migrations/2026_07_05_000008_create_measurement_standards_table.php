<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('measurement_standards', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('part_hardware_mapping_id')->unique()->constrained()->cascadeOnDelete();
            $table->decimal('min_value', 8, 2);
            $table->decimal('max_value', 8, 2);
            $table->string('unit');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('measurement_standards');
    }
};
