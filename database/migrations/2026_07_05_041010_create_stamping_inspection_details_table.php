<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stamping_inspection_details', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('inspection_record_id')->unique()->constrained()->cascadeOnDelete();

            $table->boolean('is_defect');
            $table->text('defect_remarks')->nullable(); // required (enforced in app) when is_defect = true

            $table->boolean('jig_spec_ok');
            $table->text('jig_remarks')->nullable(); // required when jig_spec_ok = false

            $table->string('manual_judgement'); // JudgementResult enum: ok | ng | repair
            $table->text('judgement_remarks')->nullable(); // required when manual_judgement != ok

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stamping_inspection_details');
    }
};
