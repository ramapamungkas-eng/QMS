<?php

use App\Models\WorkStation;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_stations', function (Blueprint $table) {
            $table->foreignId('station_type_id')->nullable()->constrained('work_station_types')->cascadeOnDelete();
        });

        Schema::table('inspection_checklist_templates', function (Blueprint $table) {
            $table->foreignId('station_type_id')->nullable()->constrained('work_station_types')->cascadeOnDelete();
        });

        // Migrate old type values to the new FK using a slug map
        $slugMap = [
            'stamping' => 'stamping',
            'station_spot' => 'station-spot',
            'portable_spot' => 'portable-spot',
            'robot_spot' => 'robot-spot',
        ];

        foreach ($slugMap as $oldType => $slug) {
            $stationTypeId = DB::table('work_station_types')->where('slug', $slug)->value('id');

            if ($stationTypeId === null) {
                continue;
            }

            WorkStation::where('type', $oldType)->update(['station_type_id' => $stationTypeId]);
            DB::table('inspection_checklist_templates')->where('work_station_type', $oldType)->update(['station_type_id' => $stationTypeId]);
        }

        // Migrate part_work_station_types
        Schema::table('part_work_station_types', function (Blueprint $table) {
            $table->foreignId('station_type_id')->nullable()->constrained('work_station_types')->cascadeOnDelete();
        });

        $partStationTypeMap = [
            'stamping' => 'stamping',
            'station_spot' => 'station-spot',
            'portable_spot' => 'portable-spot',
            'robot_spot' => 'robot-spot',
        ];

        foreach ($partStationTypeMap as $oldValue => $slug) {
            $stationTypeId = DB::table('work_station_types')->where('slug', $slug)->value('id');

            if ($stationTypeId === null) {
                continue;
            }

            DB::table('part_work_station_types')
                ->where('work_station_type', $oldValue)
                ->update(['station_type_id' => $stationTypeId]);
        }
    }

    public function down(): void
    {
        Schema::table('part_work_station_types', function (Blueprint $table) {
            $table->dropForeign(['station_type_id']);
            $table->dropColumn('station_type_id');
        });

        Schema::table('inspection_checklist_templates', function (Blueprint $table) {
            $table->dropForeign(['station_type_id']);
            $table->dropColumn('station_type_id');
        });

        Schema::table('work_stations', function (Blueprint $table) {
            $table->dropForeign(['station_type_id']);
            $table->dropColumn('station_type_id');
        });
    }
};
