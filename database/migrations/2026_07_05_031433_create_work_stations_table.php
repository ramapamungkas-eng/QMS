<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_stations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('process_id')->constrained()->cascadeOnDelete();
            $table->string('name'); // B1, B2, B3, B4, Fengyu, Station Spot, Portable Spot, Robot Spot
            $table->string('type'); // WorkStationType enum value — drives which checklist form applies
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_stations');
    }
};
