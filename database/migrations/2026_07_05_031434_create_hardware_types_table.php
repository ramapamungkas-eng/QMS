<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hardware_types', function (Blueprint $table): void {
            $table->id();
            $table->string('part_number')->unique();
            $table->string('part_name');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hardware_types');
    }
};
