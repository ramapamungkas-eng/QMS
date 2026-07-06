<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('weld_length_standards', function (Blueprint $table) {
            $table->dropUnique(['part_id']);
        });

        Schema::table('weld_length_standards', function (Blueprint $table) {
            $table->foreignId('work_station_id')->nullable()->after('part_id')->constrained()->cascadeOnDelete();
            $table->unique(['part_id', 'work_station_id']);
        });
    }

    public function down(): void
    {
        Schema::table('weld_length_standards', function (Blueprint $table) {
            $table->dropUnique(['part_id', 'work_station_id']);
            $table->dropForeign(['work_station_id']);
            $table->dropColumn('work_station_id');
        });

        Schema::table('weld_length_standards', function (Blueprint $table) {
            $table->unique('part_id');
        });
    }
};
