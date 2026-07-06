<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_stations', function (Blueprint $table) {
            $table->foreignId('station_type_id')->nullable(false)->change();
        });

        Schema::table('inspection_checklist_templates', function (Blueprint $table) {
            $table->foreignId('station_type_id')->nullable(false)->change();
        });

        Schema::table('part_work_station_types', function (Blueprint $table) {
            $table->foreignId('station_type_id')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('work_stations', function (Blueprint $table) {
            $table->foreignId('station_type_id')->nullable()->change();
        });

        Schema::table('inspection_checklist_templates', function (Blueprint $table) {
            $table->foreignId('station_type_id')->nullable()->change();
        });

        Schema::table('part_work_station_types', function (Blueprint $table) {
            $table->foreignId('station_type_id')->nullable()->change();
        });
    }
};
