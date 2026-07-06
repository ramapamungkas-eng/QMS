<?php

namespace Database\Seeders;

use App\Models\ChecklistField;
use App\Models\InspectionFieldValue;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MigrateInspectionDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->migrateStamping();
        $this->migrateStationSpot();
        $this->migratePortableSpot();
        $this->migrateRobotSpot();
    }

    protected function fieldId(string $fieldKey): ?int
    {
        return ChecklistField::where('field_key', $fieldKey)->value('id');
    }

    protected function migrateStamping(): void
    {
        if (! $this->tableExists('stamping_inspection_details')) {
            return;
        }

        $rows = DB::table('stamping_inspection_details')->get();

        foreach ($rows as $row) {
            $this->createFieldValue($row->inspection_record_id, 'is_defect', $row->is_defect ? '1' : '0');
            $this->createFieldValue($row->inspection_record_id, 'defect_remarks', $row->defect_remarks);
            $this->createFieldValue($row->inspection_record_id, 'jig_spec_ok', $row->jig_spec_ok ? '1' : '0');
            $this->createFieldValue($row->inspection_record_id, 'jig_remarks', $row->jig_remarks);
            $this->createFieldValue($row->inspection_record_id, 'manual_judgement', $row->manual_judgement);
            $this->createFieldValue($row->inspection_record_id, 'judgement_remarks', $row->judgement_remarks);
        }
    }

    protected function migrateStationSpot(): void
    {
        if (! $this->tableExists('station_spot_inspection_details')) {
            return;
        }

        $rows = DB::table('station_spot_inspection_details')
            ->orderBy('inspection_record_id')
            ->orderBy('id')
            ->get();

        $groupIndexes = [];

        foreach ($rows as $row) {
            $key = $row->inspection_record_id;
            $groupIndexes[$key] = ($groupIndexes[$key] ?? -1) + 1;

            $this->createFieldValue(
                $row->inspection_record_id,
                'measurement_value',
                (string) $row->measurement_value,
                $row->auto_judgement,
                $row->remarks,
                $groupIndexes[$key],
                $row->part_hardware_mapping_id,
            );
        }
    }

    protected function migratePortableSpot(): void
    {
        if (! $this->tableExists('portable_spot_inspection_details')) {
            return;
        }

        $rows = DB::table('portable_spot_inspection_details')->get();

        foreach ($rows as $row) {
            $this->createFieldValue($row->inspection_record_id, 'is_ok', $row->is_ok ? '1' : '0');
            $this->createFieldValue($row->inspection_record_id, 'remarks', $row->remarks);
        }
    }

    protected function migrateRobotSpot(): void
    {
        if (! $this->tableExists('robot_spot_inspection_details')) {
            return;
        }

        $rows = DB::table('robot_spot_inspection_details')->get();

        foreach ($rows as $row) {
            $this->createFieldValue($row->inspection_record_id, 'jig_ok', ! is_null($row->jig_ok) ? ($row->jig_ok ? '1' : '0') : null);
            $this->createFieldValue($row->inspection_record_id, 'jig_remarks', $row->jig_remarks);
            $this->createFieldValue($row->inspection_record_id, 'weld_length', (string) $row->weld_length, $row->auto_judgement);
        }
    }

    protected function tableExists(string $table): bool
    {
        return DB::getSchemaBuilder()->hasTable($table);
    }

    protected function createFieldValue(
        int $inspectionRecordId,
        string $fieldKey,
        ?string $value,
        ?string $autoJudgement = null,
        ?string $remarks = null,
        int $groupIndex = 0,
        ?int $sourceId = null,
    ): void {
        $fieldId = $this->fieldId($fieldKey);

        if ($fieldId === null) {
            return;
        }

        InspectionFieldValue::create([
            'inspection_record_id' => $inspectionRecordId,
            'field_id' => $fieldId,
            'value' => $value,
            'auto_judgement' => $autoJudgement,
            'remarks' => $remarks,
            'group_index' => $groupIndex,
            'source_id' => $sourceId,
        ]);
    }
}
