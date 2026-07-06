<?php

namespace Database\Seeders;

use App\Models\ChecklistTemplate;
use App\Models\StationType;
use Illuminate\Database\Seeder;

class ChecklistTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $this->createForSlug('stamping');
        $this->createForSlug('station-spot');
        $this->createForSlug('portable-spot');
        $this->createForSlug('robot-spot');
    }

    protected function typeId(string $slug): ?int
    {
        return StationType::where('slug', $slug)->value('id');
    }

    protected function createForSlug(string $slug): void
    {
        $stationTypeId = $this->typeId($slug);

        if ($stationTypeId === null) {
            return;
        }

        $template = ChecklistTemplate::updateOrCreate(
            ['station_type_id' => $stationTypeId],
            ['name' => $this->templateName($slug), 'active' => true],
        );

        match ($slug) {
            'stamping' => $this->template_stamping($template),
            'station-spot' => $this->template_station_spot($template),
            'portable-spot' => $this->template_portable_spot($template),
            'robot-spot' => $this->template_robot_spot($template),
            default => null,
        };
    }

    protected function templateName(string $slug): string
    {
        return match ($slug) {
            'stamping' => 'Stamping Inspection Checklist',
            'station-spot' => 'Station Spot Inspection Checklist',
            'portable-spot' => 'Portable Spot Inspection Checklist',
            'robot-spot' => 'Robot Spot Inspection Checklist',
            default => 'Unknown Template',
        };
    }

    protected function template_stamping(ChecklistTemplate $template): void
    {
        $section = $template->sections()->updateOrCreate(
            ['label' => 'Visual Defect Check'],
            ['order' => 1, 'allow_multiple' => false],
        );

        $section->fields()->updateOrCreate(
            ['field_key' => 'is_defect'],
            ['label' => 'Visual Defect Present?', 'field_type' => 'boolean', 'required' => true, 'order' => 1],
        );

        $section = $template->sections()->updateOrCreate(
            ['label' => 'Jig / Spec Conformance'],
            ['order' => 2, 'allow_multiple' => false],
        );

        $section->fields()->updateOrCreate(
            ['field_key' => 'jig_spec_ok'],
            ['label' => 'Jig / Spec OK?', 'field_type' => 'boolean', 'required' => true, 'order' => 1],
        );

        $section = $template->sections()->updateOrCreate(
            ['label' => 'Final Judgement'],
            ['order' => 3, 'allow_multiple' => false],
        );

        $section->fields()->updateOrCreate(
            ['field_key' => 'manual_judgement'],
            ['label' => 'Manual Judgement', 'field_type' => 'enum', 'options' => ['OK', 'NG', 'REPAIR'], 'required' => true, 'order' => 1],
        );

        $section->fields()->updateOrCreate(
            ['field_key' => 'judgement_remarks'],
            ['label' => 'Judgement Remarks', 'field_type' => 'text', 'required' => false, 'order' => 2],
        );
    }

    protected function template_station_spot(ChecklistTemplate $template): void
    {
        $section = $template->sections()->updateOrCreate(
            ['label' => 'Hardware Measurements'],
            ['order' => 1, 'allow_multiple' => true, 'source_type' => 'part_hardware_mappings'],
        );

        $section->fields()->updateOrCreate(
            ['field_key' => 'measurement_value'],
            [
                'label' => 'Measurement Value',
                'field_type' => 'numeric',
                'required' => true,
                'order' => 1,
                'has_auto_judge' => true,
                'auto_judge_source' => 'measurement_standard',
            ],
        );
    }

    protected function template_portable_spot(ChecklistTemplate $template): void
    {
        $section = $template->sections()->updateOrCreate(
            ['label' => 'Tap Test Check'],
            ['order' => 1, 'allow_multiple' => false],
        );

        $section->fields()->updateOrCreate(
            ['field_key' => 'is_ok'],
            ['label' => 'Tap Test Pass?', 'field_type' => 'boolean', 'required' => true, 'order' => 1],
        );
    }

    protected function template_robot_spot(ChecklistTemplate $template): void
    {
        $section = $template->sections()->updateOrCreate(
            ['label' => 'Visual & Jig Check'],
            ['order' => 1, 'allow_multiple' => false],
        );

        $section->fields()->updateOrCreate(
            ['field_key' => 'jig_ok'],
            ['label' => 'Jig OK?', 'field_type' => 'boolean', 'required' => false, 'order' => 1],
        );

        $section = $template->sections()->updateOrCreate(
            ['label' => 'Weld Length Measurement'],
            ['order' => 2, 'allow_multiple' => false],
        );

        $section->fields()->updateOrCreate(
            ['field_key' => 'weld_length'],
            [
                'label' => 'Weld Length (mm)',
                'field_type' => 'numeric',
                'required' => true,
                'order' => 1,
                'has_auto_judge' => true,
                'auto_judge_source' => 'weld_length_standard',
                'unit' => 'mm',
            ],
        );
    }
}
