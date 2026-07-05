<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('portable_spot_inspection_details', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('inspection_record_id')->unique()->constrained()->cascadeOnDelete();

            $table->boolean('is_ok');
            $table->text('remarks')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('portable_spot_inspection_details');
    }
};
