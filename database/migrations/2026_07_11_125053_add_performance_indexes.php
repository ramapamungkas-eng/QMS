<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // inspection_records — most queried table
        Schema::table('inspection_records', function (Blueprint $table): void {
            $table->index('part_id');
            $table->index('stage');
            $table->index('checker_id');
            $table->index('checked_at');
            $table->index(['part_id', 'stage', 'production_date'], 'insp_stage_history_idx');
            $table->index(['part_id', 'work_station_id', 'stage', 'production_date'], 'insp_recheck_idx');
        });

        // inspection_field_values — whereHas('fieldValues', 'ng') runs everywhere
        Schema::table('inspection_field_values', function (Blueprint $table): void {
            $table->index('auto_judgement');
            $table->index('source_id');
        });

        // part_work_station_types — whereHas subqueries on every board/report page
        Schema::table('part_work_station_types', function (Blueprint $table): void {
            $table->index('station_type_id');
        });

        // work_stations — frequently filtered lookup table
        Schema::table('work_stations', function (Blueprint $table): void {
            $table->index('station_type_id');
            $table->index(['process_id', 'name']);
        });

        // parts — LIKE search on part_name
        Schema::table('parts', function (Blueprint $table): void {
            $table->index('part_name');
        });

        // part_hardware_mappings — withCount queries
        Schema::table('part_hardware_mappings', function (Blueprint $table): void {
            $table->index('hardware_type_id');
        });

        // work_station_types — FK lookup
        Schema::table('work_station_types', function (Blueprint $table): void {
            $table->index('process_id');
        });

        // exports — user's export history listing
        Schema::table('exports', function (Blueprint $table): void {
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('inspection_records', function (Blueprint $table): void {
            $table->dropIndex(['part_id']);
            $table->dropIndex(['stage']);
            $table->dropIndex(['checker_id']);
            $table->dropIndex(['checked_at']);
            $table->dropIndex('insp_stage_history_idx');
            $table->dropIndex('insp_recheck_idx');
        });

        Schema::table('inspection_field_values', function (Blueprint $table): void {
            $table->dropIndex(['auto_judgement']);
            $table->dropIndex(['source_id']);
        });

        Schema::table('part_work_station_types', function (Blueprint $table): void {
            $table->dropIndex(['station_type_id']);
        });

        Schema::table('work_stations', function (Blueprint $table): void {
            $table->dropIndex(['station_type_id']);
            $table->dropIndex(['process_id', 'name']);
        });

        Schema::table('parts', function (Blueprint $table): void {
            $table->dropIndex(['part_name']);
        });

        Schema::table('part_hardware_mappings', function (Blueprint $table): void {
            $table->dropIndex(['hardware_type_id']);
        });

        Schema::table('work_station_types', function (Blueprint $table): void {
            $table->dropIndex(['process_id']);
        });

        Schema::table('exports', function (Blueprint $table): void {
            $table->dropIndex(['user_id']);
        });
    }
};
