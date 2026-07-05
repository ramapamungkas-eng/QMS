<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('robot_spot_inspection_details', function (Blueprint $table): void {
            $table->boolean('jig_ok')->nullable()->after('weld_length');
            $table->text('jig_remarks')->nullable()->after('jig_ok');
        });
    }

    public function down(): void
    {
        Schema::table('robot_spot_inspection_details', function (Blueprint $table): void {
            $table->dropColumn(['jig_ok', 'jig_remarks']);
        });
    }
};
