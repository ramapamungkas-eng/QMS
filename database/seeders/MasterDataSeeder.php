<?php

namespace Database\Seeders;

use App\Enums\MeasurementType;
use App\Models\HardwareType;
use App\Models\MeasurementStandard;
use App\Models\Part;
use App\Models\PartHardwareMapping;
use App\Models\PartWorkStationType;
use App\Models\Process;
use App\Models\StationType;
use App\Models\WeldLengthStandard;
use App\Models\WorkStation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MasterDataSeeder extends Seeder
{
    public function run(): void
    {
        // ─── Processes ─────────────────────────────────────────────────────

        $stamping = Process::updateOrCreate(['name' => 'Stamping']);
        $welding = Process::updateOrCreate(['name' => 'Welding']);

        // ─── Station Types ──────────────────────────────────────────────────

        $stampingType = StationType::updateOrCreate(
            ['slug' => 'stamping'],
            [
                'process_id' => $stamping->id,
                'name' => 'Stamping',
                'description' => 'Physical stamping press line. Visual inspection + manual judgement for defects and jig specs.',
                'icon' => 'o-cog',
            ],
        );

        $stationSpotType = StationType::updateOrCreate(
            ['slug' => 'station-spot'],
            [
                'process_id' => $welding->id,
                'name' => 'Station Spot',
                'description' => 'Fixed welding station with automated gun. Torque or nugget measurement with min/max standards.',
                'icon' => 'o-wrench-screwdriver',
            ],
        );

        $portableSpotType = StationType::updateOrCreate(
            ['slug' => 'portable-spot'],
            [
                'process_id' => $welding->id,
                'name' => 'Portable Spot',
                'description' => 'Handheld welding gun with hammer-and-chisel tap test. Visual pass/fail check.',
                'icon' => 'o-hand-raised',
            ],
        );

        $robotSpotType = StationType::updateOrCreate(
            ['slug' => 'robot-spot'],
            [
                'process_id' => $welding->id,
                'name' => 'Robot Spot',
                'description' => 'Robotic welding arm. Visual check plus weld length measurement against standards.',
                'icon' => 'o-computer-desktop',
            ],
        );

        // ─── Work Stations ──────────────────────────────────────────────────

        // Migrate old names to new names
        WorkStation::where('name', 'B1')->where('process_id', $stamping->id)->update(['name' => 'A1']);
        WorkStation::where('name', 'B2')->where('process_id', $stamping->id)->update(['name' => 'A2']);
        WorkStation::where('name', 'B3')->where('process_id', $stamping->id)->update(['name' => 'A3']);
        WorkStation::where('name', 'B4')->where('process_id', $stamping->id)->update(['name' => 'A4']);
        $welding->workStations()->where('name', 'Station Spot')->update(['name' => 'SSW']);
        $welding->workStations()->where('name', 'Portable Spot')->update(['name' => 'PSW']);
        $welding->workStations()->where('name', 'Robot Spot')->update(['name' => 'RSW']);

        // Deduplicate: keep lowest ID per (name, process_id)
        DB::statement('
            DELETE FROM work_stations
            WHERE id NOT IN (
                SELECT MIN(id) FROM work_stations GROUP BY name, process_id
            )
        ');

        // Create/update the expected set
        foreach (['A1', 'A2', 'A3', 'A4', 'A5', 'Fengyu'] as $name) {
            WorkStation::updateOrCreate(
                ['name' => $name, 'process_id' => $stamping->id],
                ['station_type_id' => $stampingType->id],
            );
        }

        foreach ([
            ['name' => 'SSW', 'type' => $stationSpotType->id],
            ['name' => 'PSW', 'type' => $portableSpotType->id],
            ['name' => 'RSW', 'type' => $robotSpotType->id],
        ] as $station) {
            WorkStation::updateOrCreate(
                ['name' => $station['name'], 'process_id' => $welding->id],
                ['station_type_id' => $station['type']],
            );
        }

        // ─── Parts ──────────────────────────────────────────────────────────

        $bodyFront = Part::updateOrCreate(
            ['part_number' => 'PR-1001'],
            ['part_name' => 'Body Panel Front', 'model' => 'Model-X', 'variant' => 'A'],
        );

        $bodyRear = Part::updateOrCreate(
            ['part_number' => 'PR-1002'],
            ['part_name' => 'Body Panel Rear', 'model' => 'Model-X', 'variant' => 'A'],
        );

        $doorLh = Part::updateOrCreate(
            ['part_number' => 'PR-1003'],
            ['part_name' => 'Door Inner LH', 'model' => 'Model-X', 'variant' => 'LHD'],
        );

        $doorRh = Part::updateOrCreate(
            ['part_number' => 'PR-1004'],
            ['part_name' => 'Door Inner RH', 'model' => 'Model-X', 'variant' => 'LHD'],
        );

        $crossMember = Part::updateOrCreate(
            ['part_number' => 'PR-2001'],
            ['part_name' => 'Cross Member Assembly', 'model' => 'Model-X', 'variant' => 'B'],
        );

        $bracket = Part::updateOrCreate(
            ['part_number' => 'PR-2002'],
            ['part_name' => 'Bracket Support', 'model' => 'Model-X', 'variant' => 'B'],
        );

        // ─── Hardware Types ─────────────────────────────────────────────────

        $nutM6 = HardwareType::updateOrCreate(
            ['part_number' => 'HT-001'],
            ['part_name' => 'M6 Hex Nut'],
        );

        $boltM8 = HardwareType::updateOrCreate(
            ['part_number' => 'HT-002'],
            ['part_name' => 'M8 Hex Bolt'],
        );

        $nutM10 = HardwareType::updateOrCreate(
            ['part_number' => 'HT-003'],
            ['part_name' => 'M10 Flange Nut'],
        );

        $studM6 = HardwareType::updateOrCreate(
            ['part_number' => 'HT-004'],
            ['part_name' => 'M6x20 Stud'],
        );

        // ─── Part ↔ Hardware Mappings + Measurement Standards ──────────────

        $this->createMappingWithStandard($bodyFront, $nutM6, MeasurementType::Torque, 4, 25, 35, 'Nm');
        $this->createMappingWithStandard($bodyFront, $boltM8, MeasurementType::Torque, 2, 30, 45, 'Nm');

        $this->createMappingWithStandard($doorLh, $nutM10, MeasurementType::Nugget, 6, 6, 10, 'mm');
        $this->createMappingWithStandard($doorRh, $nutM10, MeasurementType::Nugget, 6, 6, 10, 'mm');

        $this->createMappingWithStandard($crossMember, $studM6, MeasurementType::Torque, 8, 18, 28, 'Nm');

        // ─── Weld Length Standards (Robot Spot) ─────────────────────────────

        $robotSpotStation = WorkStation::where('station_type_id', $robotSpotType->id)->first();

        if ($robotSpotStation) {
            WeldLengthStandard::updateOrCreate(
                ['part_id' => $bodyFront->id, 'work_station_id' => $robotSpotStation->id],
                ['min_length' => 10, 'max_length' => 15, 'unit' => 'mm'],
            );

            WeldLengthStandard::updateOrCreate(
                ['part_id' => $bodyRear->id, 'work_station_id' => $robotSpotStation->id],
                ['min_length' => 10, 'max_length' => 15, 'unit' => 'mm'],
            );

            WeldLengthStandard::updateOrCreate(
                ['part_id' => $doorLh->id, 'work_station_id' => $robotSpotStation->id],
                ['min_length' => 8, 'max_length' => 12, 'unit' => 'mm'],
            );

            WeldLengthStandard::updateOrCreate(
                ['part_id' => $doorRh->id, 'work_station_id' => $robotSpotStation->id],
                ['min_length' => 8, 'max_length' => 12, 'unit' => 'mm'],
            );

            WeldLengthStandard::updateOrCreate(
                ['part_id' => $bracket->id, 'work_station_id' => $robotSpotStation->id],
                ['min_length' => 5, 'max_length' => 10, 'unit' => 'mm'],
            );
        }

        // ─── Part ↔ Station Type Associations ──────────────────────────────

        // Stamping parts
        foreach ([$bodyFront, $bodyRear, $doorLh, $doorRh] as $part) {
            PartWorkStationType::firstOrCreate([
                'part_id' => $part->id,
                'station_type_id' => $stampingType->id,
            ]);
        }

        // Station Spot parts
        foreach ([$bodyFront, $bodyRear, $doorLh, $doorRh, $crossMember] as $part) {
            PartWorkStationType::firstOrCreate([
                'part_id' => $part->id,
                'station_type_id' => $stationSpotType->id,
            ]);
        }

        // Portable Spot parts
        foreach ([$bodyFront, $bodyRear, $bracket] as $part) {
            PartWorkStationType::firstOrCreate([
                'part_id' => $part->id,
                'station_type_id' => $portableSpotType->id,
            ]);
        }

        // Robot Spot parts
        foreach ([$bodyFront, $bodyRear, $doorLh, $doorRh, $bracket] as $part) {
            PartWorkStationType::firstOrCreate([
                'part_id' => $part->id,
                'station_type_id' => $robotSpotType->id,
            ]);
        }
    }

    protected function createMappingWithStandard(
        Part $part,
        HardwareType $hardware,
        MeasurementType $measurementType,
        int $usageQty,
        float $min,
        float $max,
        string $unit,
    ): void {
        $mapping = PartHardwareMapping::updateOrCreate([
            'part_id' => $part->id,
            'hardware_type_id' => $hardware->id,
            'measurement_type' => $measurementType,
        ], [
            'usage_qty' => $usageQty,
        ]);

        MeasurementStandard::updateOrCreate(
            ['part_hardware_mapping_id' => $mapping->id],
            ['min_value' => $min, 'max_value' => $max, 'unit' => $unit],
        );
    }
}
