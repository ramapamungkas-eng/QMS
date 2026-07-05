<?php

namespace Database\Seeders;

use App\Enums\WorkStationType;
use App\Models\InspectionRecord;
use App\Models\PartWorkStationType;
use App\Models\Process;
use App\Models\WorkStation;
use Illuminate\Database\Seeder;

class MasterDataSeeder extends Seeder
{
    public function run(): void
    {
        $stamping = Process::updateOrCreate(['name' => 'Stamping']);
        $welding = Process::updateOrCreate(['name' => 'Welding']);

        foreach (['B1', 'B2', 'B3', 'B4', 'Fengyu'] as $name) {
            $stamping->workStations()->updateOrCreate(
                ['name' => $name],
                ['type' => WorkStationType::Stamping]
            );
        }

        $welding->workStations()->updateOrCreate(
            ['name' => 'Station Spot'],
            ['type' => WorkStationType::StationSpot]
        );

        $welding->workStations()->updateOrCreate(
            ['name' => 'Portable Spot'],
            ['type' => WorkStationType::PortableSpot]
        );

        $welding->workStations()->updateOrCreate(
            ['name' => 'Robot Spot'],
            ['type' => WorkStationType::RobotSpot]
        );

        // Sync existing parts with station types based on inspection records
        $stampingStationIds = WorkStation::where('type', WorkStationType::Stamping)->pluck('id');
        $stampingPartIds = InspectionRecord::whereIn('work_station_id', $stampingStationIds)
            ->distinct('part_id')
            ->pluck('part_id');
        foreach ($stampingPartIds as $partId) {
            PartWorkStationType::firstOrCreate([
                'part_id' => $partId,
                'work_station_type' => WorkStationType::Stamping->value,
            ]);
        }

        $weldingStationTypes = [WorkStationType::StationSpot, WorkStationType::PortableSpot, WorkStationType::RobotSpot];
        foreach ($weldingStationTypes as $type) {
            $stationIds = WorkStation::where('type', $type)->pluck('id');
            $partIds = InspectionRecord::whereIn('work_station_id', $stationIds)
                ->distinct('part_id')
                ->pluck('part_id');
            foreach ($partIds as $partId) {
                PartWorkStationType::firstOrCreate([
                    'part_id' => $partId,
                    'work_station_type' => $type->value,
                ]);
            }
        }
    }
}
