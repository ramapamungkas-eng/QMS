<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('station_spot_inspection_details');
        Schema::dropIfExists('robot_spot_inspection_details');
        Schema::dropIfExists('portable_spot_inspection_details');
        Schema::dropIfExists('stamping_inspection_details');
    }

    public function down(): void
    {
        // Recreate is not practical — data lives in inspection_field_values now
    }
};
