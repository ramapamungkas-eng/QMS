<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('station_spot_inspection_details', function (Blueprint $table): void {
            $table->text('remarks')->nullable()->after('auto_judgement');
        });
    }

    public function down(): void
    {
        Schema::table('station_spot_inspection_details', function (Blueprint $table): void {
            $table->dropColumn('remarks');
        });
    }
};
