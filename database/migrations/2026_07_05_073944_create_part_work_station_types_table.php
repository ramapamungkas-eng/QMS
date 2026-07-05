<?php

use App\Enums\WorkStationType;
use App\Models\InspectionRecord;
use App\Models\Part;
use App\Models\WorkStation;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('part_work_station_types', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('part_id')->constrained()->cascadeOnDelete();
            $table->string('work_station_type');
            $table->timestamps();

            $table->unique(['part_id', 'work_station_type']);
        });

        // Migrate existing data from part_process + inspection records
        $stampingStationIds = WorkStation::where('type', WorkStationType::Stamping)->pluck('id');
        $weldingStationIds = WorkStation::whereIn('type', [
            WorkStationType::StationSpot,
            WorkStationType::PortableSpot,
            WorkStationType::RobotSpot,
        ])->pluck('id');

        Part::query()->each(function (Part $part) use ($stampingStationIds, $weldingStationIds): void {
            $hasStamping = InspectionRecord::where('part_id', $part->id)
                ->whereIn('work_station_id', $stampingStationIds)
                ->exists();

            $weldingTypes = InspectionRecord::where('part_id', $part->id)
                ->whereIn('work_station_id', $weldingStationIds)
                ->with('workStation')
                ->get()
                ->pluck('workStation.type')
                ->unique()
                ->map(fn ($type) => $type->value);

            if ($hasStamping) {
                DB::table('part_work_station_types')->insert([
                    'part_id' => $part->id,
                    'work_station_type' => WorkStationType::Stamping->value,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            foreach ($weldingTypes as $type) {
                DB::table('part_work_station_types')->insert([
                    'part_id' => $part->id,
                    'work_station_type' => $type,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });

        Schema::dropIfExists('part_process');
    }

    public function down(): void
    {
        Schema::create('part_process', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('part_id')->constrained()->cascadeOnDelete();
            $table->foreignId('process_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['part_id', 'process_id']);
        });

        // Restore from part_work_station_types
        Part::query()->each(function (Part $part): void {
            $types = DB::table('part_work_station_types')
                ->where('part_id', $part->id)
                ->pluck('work_station_type');

            if ($types->contains(WorkStationType::Stamping->value)) {
                DB::table('part_process')->insert([
                    'part_id' => $part->id,
                    'process_id' => 1, // Stamping
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $weldingTypes = collect([WorkStationType::StationSpot, WorkStationType::PortableSpot, WorkStationType::RobotSpot])
                ->map(fn ($t) => $t->value)
                ->filter(fn ($t) => $types->contains($t));

            if ($weldingTypes->isNotEmpty()) {
                DB::table('part_process')->insert([
                    'part_id' => $part->id,
                    'process_id' => 2, // Welding
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });

        Schema::dropIfExists('part_work_station_types');
    }
};
