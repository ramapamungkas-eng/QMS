<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_stations', function (Blueprint $table) {
            $table->dropColumn('type');
        });

        Schema::table('inspection_checklist_templates', function (Blueprint $table) {
            $table->dropUnique(['work_station_type']);
            $table->dropColumn('work_station_type');
        });

        Schema::table('part_work_station_types', function (Blueprint $table) {
            $table->dropUnique(['part_id', 'work_station_type']);
            $table->dropColumn('work_station_type');
        });
    }

    public function down(): void
    {
        Schema::table('work_stations', function (Blueprint $table) {
            $table->string('type')->nullable();
        });

        Schema::table('inspection_checklist_templates', function (Blueprint $table) {
            $table->string('work_station_type')->nullable();
        });

        Schema::table('part_work_station_types', function (Blueprint $table) {
            $table->string('work_station_type')->nullable();
        });
    }
};
