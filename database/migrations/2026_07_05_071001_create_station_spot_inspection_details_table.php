<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('station_spot_inspection_details', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('inspection_record_id')->constrained()->cascadeOnDelete();
            $table->foreignId('part_hardware_mapping_id')->constrained()->cascadeOnDelete();

            $table->decimal('measurement_value', 10, 2);
            $table->string('auto_judgement'); // Ok | Ng

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('station_spot_inspection_details');
    }
};
