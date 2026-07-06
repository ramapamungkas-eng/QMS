<?php

use App\Models\ChecklistField;
use App\Models\ChecklistSection;
use App\Models\ChecklistTemplate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Mary\Traits\Toast;

new
#[Layout('layouts.app')]
#[Title('Edit checklist template')]
class extends Component {
    use Toast;

    public ChecklistTemplate $template;

    public string $name;

    public bool $active = true;

    // --- Section modal ---
    public bool $showSectionModal = false;

    public ?int $editingSectionId = null;

    public string $section_label = '';

    public bool $section_allow_multiple = false;

    public string $section_source_type = '';

    // --- Field modal ---
    public bool $showFieldModal = false;

    public ?int $editingFieldId = null;

    public string $field_section_id = '';

    public string $field_field_key = '';

    public string $field_label = '';

    public string $field_field_type = 'text';

    public ?string $field_options = null;

    public bool $field_required = false;

    public string $field_order = '0';

    public bool $field_has_auto_judge = false;

    public string $field_auto_judge_source = '';

    public string $field_min_value = '';

    public string $field_max_value = '';

    public string $field_unit = '';

    public function mount(ChecklistTemplate $template): void
    {
        $this->template = $template->load('sections.fields');
        $this->name = $template->name;
        $this->active = $template->active;
    }

    // --- Template save ---

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'active' => ['boolean'],
        ];
    }

    public function save(): void
    {
        $data = $this->validate();

        $this->template->update($data);

        $this->success('Template updated.', position: 'toast-bottom');
    }

    // --- Section CRUD ---

    public function sectionValidation(): array
    {
        return [
            'section_label' => ['required', 'string', 'max:255'],
            'section_allow_multiple' => ['boolean'],
            'section_source_type' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function openCreateSection(): void
    {
        $this->reset([
            'editingSectionId',
            'section_label',
            'section_allow_multiple',
            'section_source_type',
        ]);
        $this->section_allow_multiple = false;
        $this->resetValidation();
        $this->showSectionModal = true;
    }

    public function openEditSection(int $sectionId): void
    {
        $section = ChecklistSection::findOrFail($sectionId);

        $this->editingSectionId = $section->id;
        $this->section_label = $section->label;
        $this->section_allow_multiple = $section->allow_multiple;
        $this->section_source_type = (string) $section->source_type;

        $this->resetValidation();
        $this->showSectionModal = true;
    }

    public function saveSection(): void
    {
        $data = $this->validate($this->sectionValidation());

        if ($this->editingSectionId) {
            $section = ChecklistSection::findOrFail($this->editingSectionId);
            $section->update([
                'label' => $data['section_label'],
                'allow_multiple' => $data['section_allow_multiple'],
                'source_type' => $data['section_source_type'] ?: null,
            ]);
        } else {
            $maxOrder = $this->template->sections()->max('order') ?? 0;
            $this->template->sections()->create([
                'label' => $data['section_label'],
                'order' => $maxOrder + 1,
                'allow_multiple' => $data['section_allow_multiple'],
                'source_type' => $data['section_source_type'] ?: null,
            ]);
        }

        $this->showSectionModal = false;
        $this->template->load('sections.fields');
        $this->success('Section saved.', position: 'toast-bottom');
    }

    public function deleteSection(int $sectionId): void
    {
        ChecklistSection::findOrFail($sectionId)->delete();
        $this->template->load('sections.fields');
        $this->success('Section deleted.', position: 'toast-bottom');
    }

    public function moveSectionUp(int $sectionId): void
    {
        $section = ChecklistSection::findOrFail($sectionId);
        $prev = ChecklistSection::where('template_id', $this->template->id)
            ->where('order', '<', $section->order)
            ->orderByDesc('order')
            ->first();

        if ($prev) {
            $tmp = $section->order;
            $section->update(['order' => $prev->order]);
            $prev->update(['order' => $tmp]);
        }

        $this->template->load('sections.fields');
    }

    public function moveSectionDown(int $sectionId): void
    {
        $section = ChecklistSection::findOrFail($sectionId);
        $next = ChecklistSection::where('template_id', $this->template->id)
            ->where('order', '>', $section->order)
            ->orderBy('order')
            ->first();

        if ($next) {
            $tmp = $section->order;
            $section->update(['order' => $next->order]);
            $next->update(['order' => $tmp]);
        }

        $this->template->load('sections.fields');
    }

    // --- Field CRUD ---

    public function fieldValidation(): array
    {
        $uniqueRule = Rule::unique('inspection_checklist_fields', 'field_key')
            ->where(fn ($q) => $q->where('section_id', $this->field_section_id));

        if ($this->editingFieldId) {
            $uniqueRule->ignore($this->editingFieldId);
        }

        return [
            'field_section_id' => ['required', 'exists:inspection_checklist_sections,id'],
            'field_field_key' => ['required', 'string', 'max:100', $uniqueRule],
            'field_label' => ['required', 'string', 'max:255'],
            'field_field_type' => ['required', Rule::in(['boolean', 'numeric', 'enum', 'text'])],
            'field_options' => ['nullable', 'string', 'max:1000'],
            'field_required' => ['boolean'],
            'field_order' => ['required', 'integer', 'min:0', 'max:255'],
            'field_has_auto_judge' => ['boolean'],
            'field_auto_judge_source' => ['nullable', 'required_if:field_has_auto_judge,true', Rule::in(['limits', 'measurement_standard', 'weld_length_standard'])],
            'field_min_value' => ['nullable', 'numeric'],
            'field_max_value' => ['nullable', 'numeric'],
            'field_unit' => ['nullable', 'string', 'max:20'],
        ];
    }

    public function openCreateField(int $sectionId): void
    {
        $this->reset([
            'editingFieldId',
            'field_section_id',
            'field_field_key',
            'field_label',
            'field_field_type',
            'field_options',
            'field_required',
            'field_order',
            'field_has_auto_judge',
            'field_auto_judge_source',
            'field_min_value',
            'field_max_value',
            'field_unit',
        ]);
        $this->field_section_id = (string) $sectionId;
        $this->field_field_type = 'text';
        $this->field_order = '0';
        $this->resetValidation();
        $this->showFieldModal = true;
    }

    public function openEditField(int $fieldId): void
    {
        $field = ChecklistField::findOrFail($fieldId);

        $this->editingFieldId = $field->id;
        $this->field_section_id = (string) $field->section_id;
        $this->field_field_key = $field->field_key;
        $this->field_label = $field->label;
        $this->field_field_type = $field->field_type;
        $this->field_options = $field->options ? implode(',', $field->options) : '';
        $this->field_required = $field->required;
        $this->field_order = (string) $field->order;
        $this->field_has_auto_judge = $field->has_auto_judge;
        $this->field_auto_judge_source = (string) $field->auto_judge_source;
        $this->field_min_value = (string) $field->min_value;
        $this->field_max_value = (string) $field->max_value;
        $this->field_unit = (string) $field->unit;

        $this->resetValidation();
        $this->showFieldModal = true;
    }

    public function saveField(): void
    {
        $data = $this->validate($this->fieldValidation());

        $fieldData = [
            'section_id' => $data['field_section_id'],
            'field_key' => $data['field_field_key'],
            'label' => $data['field_label'],
            'field_type' => $data['field_field_type'],
            'options' => $data['field_field_type'] === 'enum' && $data['field_options']
                ? array_map('trim', explode(',', $data['field_options']))
                : null,
            'required' => $data['field_required'],
            'order' => $data['field_order'],
            'has_auto_judge' => $data['field_has_auto_judge'],
            'auto_judge_source' => $data['field_has_auto_judge'] ? $data['field_auto_judge_source'] : null,
            'min_value' => $data['field_min_value'] !== '' ? $data['field_min_value'] : null,
            'max_value' => $data['field_max_value'] !== '' ? $data['field_max_value'] : null,
            'unit' => $data['field_unit'] ?: null,
        ];

        if ($this->editingFieldId) {
            ChecklistField::findOrFail($this->editingFieldId)->update($fieldData);
        } else {
            ChecklistSection::findOrFail($data['field_section_id'])->fields()->create($fieldData);
        }

        $this->showFieldModal = false;
        $this->template->load('sections.fields');
        $this->success('Field saved.', position: 'toast-bottom');
    }

    public function deleteField(int $fieldId): void
    {
        ChecklistField::findOrFail($fieldId)->delete();
        $this->template->load('sections.fields');
        $this->success('Field deleted.', position: 'toast-bottom');
    }

    public function moveFieldUp(int $fieldId): void
    {
        $field = ChecklistField::findOrFail($fieldId);
        $prev = ChecklistField::where('section_id', $field->section_id)
            ->where('order', '<', $field->order)
            ->orderByDesc('order')
            ->first();

        if ($prev) {
            $tmp = $field->order;
            $field->update(['order' => $prev->order]);
            $prev->update(['order' => $tmp]);
        }

        $this->template->load('sections.fields');
    }

    public function moveFieldDown(int $fieldId): void
    {
        $field = ChecklistField::findOrFail($fieldId);
        $next = ChecklistField::where('section_id', $field->section_id)
            ->where('order', '>', $field->order)
            ->orderBy('order')
            ->first();

        if ($next) {
            $tmp = $field->order;
            $field->update(['order' => $next->order]);
            $next->update(['order' => $tmp]);
        }

        $this->template->load('sections.fields');
    }

    public function with(): array
    {
        return [
            'sections' => $this->template->sections,
        ];
    }
}; ?>

<div>
    <x-header title="Edit template" subtitle="{{ $template->stationType?->name ?? '—' }}" separator>
        <x-slot:actions>
            <x-button label="Back to list" link="{{ route('checklists.index') }}" icon="o-arrow-left" responsive />
        </x-slot:actions>
    </x-header>

    <div class="grid gap-6 lg:grid-cols-12">
        <!-- SIDEBAR -->
        <div class="lg:col-span-3">
            <div class="sticky top-4 rounded-2xl border border-base-300 bg-base-100 p-5">
                <x-form wire:submit="save">
                    <div class="space-y-4">
                        <x-input label="Template name" wire:model="name" icon="o-document-text" />
                        <x-toggle label="Active" wire:model="active" hint="Inactive templates are not used for inspections." />

                        <div class="rounded-xl bg-base-200/60 px-4 py-3 text-sm text-base-content/70">
                            <div class="flex items-center gap-2">
                                <x-icon name="o-cpu-chip" class="h-4 w-4 shrink-0" />
                                <span>{{ $template->stationType?->process?->name ?? '—' }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <x-icon name="o-wrench" class="h-4 w-4 shrink-0" />
                                <span>{{ $template->stationType?->name ?? '—' }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <x-icon name="o-cube" class="h-4 w-4 shrink-0" />
                                <span>{{ $sections->count() }} section(s)</span>
                            </div>
                        </div>
                    </div>

                    <x-slot:actions>
                        <x-button label="Save" icon="o-check" class="btn-primary btn-sm" type="submit" spinner="save" />
                    </x-slot:actions>
                </x-form>
            </div>
        </div>

        <!-- MAIN -->
        <div class="grid gap-6 lg:col-span-9">
            @forelse ($sections as $section)
                <x-card :title="$section->label" shadow separator>
                    <x-slot:menu>
                        <div class="flex items-center gap-1">
                            @if (!$loop->first)
                                <x-button icon="o-chevron-up" wire:click="moveSectionUp({{ $section->id }})" class="btn-ghost btn-xs" />
                            @endif
                            @if (!$loop->last)
                                <x-button icon="o-chevron-down" wire:click="moveSectionDown({{ $section->id }})" class="btn-ghost btn-xs" />
                            @endif
                            <x-button icon="o-pencil" wire:click="openEditSection({{ $section->id }})" class="btn-ghost btn-xs" />
                            <x-button
                                icon="o-trash"
                                wire:click="deleteSection({{ $section->id }})"
                                wire:confirm="Delete this section and its fields?"
                                spinner
                                class="btn-ghost btn-xs text-error"
                            />
                        </div>
                    </x-slot:menu>

                    <div class="mb-3 flex flex-wrap gap-3 text-xs text-base-content/50">
                        <span>
                            <x-icon name="o-arrows-up-down" class="me-1 inline h-3 w-3" />
                            Order: {{ $section->order }}
                        </span>
                        <span>
                            <x-icon name="o-list-bullet" class="me-1 inline h-3 w-3" />
                            Multiple rows: {{ $section->allow_multiple ? 'Yes' : 'No' }}
                        </span>
                        @if ($section->source_type)
                            <span>
                                <x-icon name="o-circle-stack" class="me-1 inline h-3 w-3" />
                                Source: {{ $section->source_type }}
                            </span>
                        @endif
                    </div>

                    @if ($section->fields->isNotEmpty())
                        <div class="divide-y divide-base-200 overflow-hidden rounded-xl border border-base-200">
                            @foreach ($section->fields as $field)
                                <div class="flex items-center justify-between gap-3 px-4 py-3 hover:bg-base-200/30">
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center gap-2">
                                            <span class="font-medium text-sm">{{ $field->label }}</span>
                                            @if ($field->required)
                                                <x-icon name="o-exclamation-circle" class="h-3.5 w-3.5 text-error" title="Required" />
                                            @endif
                                        </div>
                                        <div class="flex flex-wrap gap-x-3 gap-y-1 text-xs text-base-content/50">
                                            <span class="font-mono">{{ $field->field_key }}</span>
                                            <x-badge :value="$field->field_type" class="badge-xs badge-ghost" />
                                            <span>Order: {{ $field->order }}</span>
                                            @if ($field->has_auto_judge)
                                                <x-badge value="Auto-judge: {{ $field->auto_judge_source }}" class="badge-xs badge-info" />
                                            @endif
                                            @if ($field->unit)
                                                <span>Unit: {{ $field->unit }}</span>
                                            @endif
                                            @if ($field->options)
                                                <span class="truncate max-w-40">Options: {{ implode(', ', $field->options) }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="flex shrink-0 items-center gap-1">
                                        @if (!$loop->first)
                                            <x-button icon="o-chevron-up" wire:click="moveFieldUp({{ $field->id }})" class="btn-ghost btn-xs" />
                                        @endif
                                        @if (!$loop->last)
                                            <x-button icon="o-chevron-down" wire:click="moveFieldDown({{ $field->id }})" class="btn-ghost btn-xs" />
                                        @endif
                                        <x-button icon="o-pencil" wire:click="openEditField({{ $field->id }})" class="btn-ghost btn-xs" />
                                        <x-button
                                            icon="o-trash"
                                            wire:click="deleteField({{ $field->id }})"
                                            wire:confirm="Delete this field?"
                                            spinner
                                            class="btn-ghost btn-xs text-error"
                                        />
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="rounded-lg border border-dashed border-base-300 px-4 py-6 text-center text-sm text-base-content/50">
                            No fields yet.
                        </p>
                    @endif

                    <x-slot:actions>
                        <x-button label="Add field" icon="o-plus" wire:click="openCreateField({{ $section->id }})" class="btn-ghost btn-sm" />
                    </x-slot:actions>
                </x-card>
            @empty
                <x-card title="No sections yet" shadow>
                    <p class="rounded-lg border border-dashed border-base-300 px-4 py-8 text-center text-sm text-base-content/50">
                        Start building the checklist form by adding a section.
                    </p>
                </x-card>
            @endforelse

            <x-button label="Add section" icon="o-plus" wire:click="openCreateSection" class="btn-outline btn-sm w-full" />
        </div>
    </div>

    <!-- SECTION MODAL -->
    <x-modal wire:model="showSectionModal" :title="$editingSectionId ? 'Edit section' : 'New section'" separator>
        <div class="grid gap-4">
            <x-input label="Label" wire:model="section_label" placeholder="e.g. Visual Check" />
            <x-toggle label="Allow multiple rows" wire:model="section_allow_multiple" hint="For multi-row data like Station Spot hardware measurements." />
            <x-input label="Source type" wire:model="section_source_type" placeholder="part_hardware_mappings" hint="Nullable. e.g. part_hardware_mappings" />
        </div>

        <x-slot:actions>
            <x-button label="Cancel" @click="$wire.showSectionModal = false" />
            <x-button label="Save" icon="o-check" class="btn-primary" wire:click="saveSection" spinner="saveSection" />
        </x-slot:actions>
    </x-modal>

    <!-- FIELD MODAL -->
    <x-modal wire:model="showFieldModal" :title="$editingFieldId ? 'Edit field' : 'New field'" separator class="w-11/12 max-w-2xl">
        <div class="grid gap-4">
            <div class="grid grid-cols-2 gap-4">
                <x-input label="Field key" wire:model="field_field_key" placeholder="is_defect" hint="Machine name. Unique within the section." />
                <x-input label="Label" wire:model="field_label" placeholder="Is there a defect?" />
            </div>

            <div class="grid grid-cols-2 gap-4">
                <x-select
                    label="Field type"
                    wire:model="field_field_type"
                    :options="[
                        ['id' => 'boolean', 'name' => 'Boolean (Yes/No)'],
                        ['id' => 'numeric', 'name' => 'Numeric value'],
                        ['id' => 'enum', 'name' => 'Enum (dropdown)'],
                        ['id' => 'text', 'name' => 'Text / remarks'],
                    ]"
                />
                <x-input label="Order" type="number" min="0" wire:model="field_order" hint="Display order." />
            </div>

            <x-toggle label="Required" wire:model="field_required" />

            @if ($field_field_type === 'enum')
                <x-input label="Options" wire:model="field_options" placeholder="OK,NG,REPAIR" hint="Comma-separated values." />
            @endif

            <hr class="border-base-300" />

            <x-toggle label="Auto-judge" wire:model="field_has_auto_judge" hint="Enable automatic OK/NG judgement." />

            @if ($field_has_auto_judge)
                <x-select
                    label="Auto-judge source"
                    wire:model="field_auto_judge_source"
                    :options="[
                        ['id' => 'limits', 'name' => 'Limits (min/max)'],
                        ['id' => 'measurement_standard', 'name' => 'Measurement standard'],
                        ['id' => 'weld_length_standard', 'name' => 'Weld length standard'],
                    ]"
                />

                <div class="grid grid-cols-3 gap-4">
                    <x-input label="Min value" wire:model="field_min_value" hint="For limits-based auto-judge." />
                    <x-input label="Max value" wire:model="field_max_value" hint="For limits-based auto-judge." />
                    <x-input label="Unit" wire:model="field_unit" placeholder="mm" />
                </div>
            @endif
        </div>

        <x-slot:actions>
            <x-button label="Cancel" @click="$wire.showFieldModal = false" />
            <x-button label="Save" icon="o-check" class="btn-primary" wire:click="saveField" spinner="saveField" />
        </x-slot:actions>
    </x-modal>
</div>
