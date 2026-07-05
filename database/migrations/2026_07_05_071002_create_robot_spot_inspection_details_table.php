<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('robot_spot_inspection_details', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('inspection_record_id')->unique()->constrained()->cascadeOnDelete();

            $table->decimal('weld_length', 8, 2);
            $table->string('auto_judgement'); // Ok | Ng

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('robot_spot_inspection_details');
    }
};
