<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inspection_checklist_templates', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('station_type_id')->constrained('work_station_types')->cascadeOnDelete();
            $table->string('name');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('inspection_checklist_sections', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('template_id')->constrained('inspection_checklist_templates')->cascadeOnDelete();
            $table->string('label');
            $table->unsignedTinyInteger('order')->default(0);
            $table->boolean('allow_multiple')->default(false);
            $table->string('source_type')->nullable();
            $table->timestamps();
        });

        Schema::create('inspection_checklist_fields', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('section_id')->constrained('inspection_checklist_sections')->cascadeOnDelete();
            $table->string('field_key');
            $table->string('label');
            $table->string('field_type');
            $table->json('options')->nullable();
            $table->boolean('required')->default(false);
            $table->unsignedTinyInteger('order')->default(0);
            $table->boolean('has_auto_judge')->default(false);
            $table->string('auto_judge_source')->nullable();
            $table->decimal('min_value', 10, 2)->nullable();
            $table->decimal('max_value', 10, 2)->nullable();
            $table->string('unit')->nullable();
            $table->timestamps();
        });

        Schema::create('inspection_field_values', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('inspection_record_id')->constrained()->cascadeOnDelete();
            $table->foreignId('field_id')->constrained('inspection_checklist_fields')->cascadeOnDelete();
            $table->text('value')->nullable();
            $table->string('auto_judgement')->nullable();
            $table->text('remarks')->nullable();
            $table->unsignedSmallInteger('group_index')->default(0);
            $table->unsignedBigInteger('source_id')->nullable();
            $table->timestamps();

            $table->index(['inspection_record_id', 'field_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inspection_field_values');
        Schema::dropIfExists('inspection_checklist_fields');
        Schema::dropIfExists('inspection_checklist_sections');
        Schema::dropIfExists('inspection_checklist_templates');
    }
};
