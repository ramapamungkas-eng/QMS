<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Layout('layouts.app')]
#[Title('Checklist Template Tutorial')]
class extends Component
{
    //
}; ?>

<div class="max-w-4xl mx-auto space-y-6">
    <x-header
        title="Checklist Template Tutorial"
        subtitle="Learn how to create and manage inspection checklist templates."
        separator
    >
        <x-slot:actions>
            <x-button
                label="Back to Checklists"
                link="{{ route('checklists.index') }}"
                icon="o-arrow-left"
                class="btn-soft"
                responsive
            />
        </x-slot:actions>
    </x-header>

    {{-- Overview --}}
    <x-card title="What is a Checklist Template?" shadow>
        <p class="text-base-content/80">
            A <strong>checklist template</strong> defines the inspection form layout for a given work station type (Stamping, Station Spot, Portable Spot, or Robot Spot). Each template is composed of <strong>sections</strong> that group related <strong>fields</strong> together.
        </p>
    </x-card>

    {{-- Step 1 --}}
    <x-card title="Step 1: Create a Template" shadow separator>
        <p class="mb-4 text-base-content/80">
            Navigate to <strong>Master Data &rarr; Checklists</strong> and click <strong>New Template</strong>.
        </p>

        <div class="overflow-x-auto">
            <table class="table table-zebra">
                <thead>
                    <tr>
                        <th class="w-40">Field</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="font-medium">Station Type</td>
                        <td>Which work station type this template is for. Each station type can have one active template at a time.</td>
                    </tr>
                    <tr>
                        <td class="font-medium">Template Name</td>
                        <td>A descriptive name, e.g. "Welding Station Spot Checklist".</td>
                    </tr>
                    <tr>
                        <td class="font-medium">Active</td>
                        <td>Toggle to enable/disable. Only active templates are used when checkers submit inspections.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <p class="mt-4 text-base-content/60 text-sm">
            After saving, you land on the edit page where you add sections and fields.
        </p>
    </x-card>

    {{-- Step 2: Sections --}}
    <x-card title="Step 2: Add Sections" shadow separator>
        <p class="mb-4 text-base-content/80">
            A <strong>section</strong> groups related inspection points. Click <strong>Add Section</strong> to create one.
        </p>

        <div class="overflow-x-auto">
            <table class="table table-zebra">
                <thead>
                    <tr>
                        <th class="w-40">Setting</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="font-medium">Label</td>
                        <td>Section heading shown on the inspection form.</td>
                    </tr>
                    <tr>
                        <td class="font-medium">Allow Multiple</td>
                        <td>Enable for repeating rows (e.g. Station Spot's per-hardware measurements). Disabled for single-row sections like Visual Checks.</td>
                    </tr>
                    <tr>
                        <td class="font-medium">Source Type</td>
                        <td>Required when <strong>Allow Multiple</strong> is on. Currently only <code>part_hardware_mappings</code> is supported — it dynamically creates one row per hardware mapping assigned to the selected part.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <h3 class="font-semibold mt-6 mb-2">Section Examples</h3>
        <div class="overflow-x-auto">
            <table class="table table-zebra">
                <thead>
                    <tr>
                        <th>Section Type</th>
                        <th>Allow Multiple</th>
                        <th>Source Type</th>
                        <th>Example</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Visual checks</td>
                        <td class="text-base-content/50">No</td>
                        <td class="text-base-content/50">(none)</td>
                        <td>Stamping's "Visual Defect Check"</td>
                    </tr>
                    <tr>
                        <td>Hardware measurements</td>
                        <td><x-badge value="Yes" class="badge-info" /></td>
                        <td><code>part_hardware_mappings</code></td>
                        <td>Station Spot's hardware rows</td>
                    </tr>
                    <tr>
                        <td>Single measurement</td>
                        <td class="text-base-content/50">No</td>
                        <td class="text-base-content/50">(none)</td>
                        <td>Robot Spot's weld length</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="mt-4 p-4 bg-base-200 rounded-box text-sm">
            <x-icon name="o-light-bulb" class="h-4 w-4 inline" />
            <span class="font-semibold">Tip:</span> Use the <strong>move up / move down</strong> buttons on sections to reorder them.
        </div>
    </x-card>

    {{-- Step 3: Fields --}}
    <x-card title="Step 3: Add Fields" shadow separator>
        <p class="mb-4 text-base-content/80">
            Within each section, click <strong>Add Field</strong> to create an inspection point.
        </p>

        <h3 class="font-semibold mb-2">Basic Settings</h3>
        <div class="overflow-x-auto mb-6">
            <table class="table table-zebra">
                <thead>
                    <tr>
                        <th class="w-40">Setting</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="font-medium">Field Key</td>
                        <td>Machine name (<code>snake_case</code>). Used internally. Must be unique within the section. Examples: <code>is_defect</code>, <code>weld_length</code>.</td>
                    </tr>
                    <tr>
                        <td class="font-medium">Label</td>
                        <td>Display text shown to the checker on the inspection form.</td>
                    </tr>
                    <tr>
                        <td class="font-medium">Field Type</td>
                        <td>Controls the input widget. See table below.</td>
                    </tr>
                    <tr>
                        <td class="font-medium">Order</td>
                        <td>Display order within the section (auto-incremented).</td>
                    </tr>
                    <tr>
                        <td class="font-medium">Required</td>
                        <td>If checked, the form enforces that a value must be entered.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <h3 class="font-semibold mb-2">Field Types</h3>
        <div class="overflow-x-auto mb-6">
            <table class="table table-zebra">
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Widget</th>
                        <th>Use Case</th>
                        <th>Example</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><x-badge value="boolean" class="badge-primary" /></td>
                        <td>Yes / No radio cards</td>
                        <td>Pass/fail checks</td>
                        <td>"Visual Defect Present?"</td>
                    </tr>
                    <tr>
                        <td><x-badge value="numeric" class="badge-secondary" /></td>
                        <td>Number input</td>
                        <td>Measurements</td>
                        <td>Weld length (mm), torque value</td>
                    </tr>
                    <tr>
                        <td><x-badge value="enum" class="badge-accent" /></td>
                        <td>Radio cards with options</td>
                        <td>Choice from predefined values</td>
                        <td>Manual judgement: OK / NG / REPAIR</td>
                    </tr>
                    <tr>
                        <td><x-badge value="text" class="badge-ghost" /></td>
                        <td>Textarea</td>
                        <td>Free-text notes</td>
                        <td>Remarks, comments</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="p-4 bg-base-200 rounded-box mb-6 text-sm">
            <span class="font-semibold">For enum fields:</span>
            Enter the options as a comma-separated list in the <strong>Options</strong> field. Example: <code>OK,NG,REPAIR</code>
        </div>

        <h3 class="font-semibold mb-2">Auto-Judgement</h3>
        <p class="mb-2 text-base-content/80">
            Enable <strong>Has Auto Judge</strong> to have the system automatically determine OK/NG for numeric fields. Then choose the source:
        </p>
        <div class="overflow-x-auto">
            <table class="table table-zebra">
                <thead>
                    <tr>
                        <th class="w-48">Source</th>
                        <th>Description</th>
                        <th>Applies To</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>limits</code></td>
                        <td>Compares the value against the field's own <strong>Min Value</strong> / <strong>Max Value</strong>.</td>
                        <td>Simple ranges (e.g. temperature 20–30°C)</td>
                    </tr>
                    <tr>
                        <td><code>measurement_standard</code></td>
                        <td>Looks up the standard from <code>measurement_standards</code> via the part's hardware mapping.</td>
                        <td>Station Spot torque/nugget values</td>
                    </tr>
                    <tr>
                        <td><code>weld_length_standard</code></td>
                        <td>Looks up min/max from <code>weld_length_standards</code> by part + work station.</td>
                        <td>Robot Spot weld length</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <p class="mt-4 text-base-content/60 text-sm">
            <strong>Unit</strong>: Display label shown after the value (e.g. <code>mm</code>, <code>Nm</code>).
        </p>
    </x-card>

    {{-- Step 4 --}}
    <x-card title="Step 4: Save & Test" shadow separator>
        <ol class="list-decimal list-inside space-y-2 text-base-content/80">
            <li>Each section and field saves independently via inline modals.</li>
            <li>
                After setting up the template, visit the corresponding inspection page:
                <ul class="list-disc list-inside ml-6 mt-1 space-y-1">
                    <li><code>/inspections/{slug}</code> to see the board</li>
                    <li><code>/inspections/{slug}/create</code> to test the form</li>
                </ul>
            </li>
            <li>If the form doesn't look right, go back to the edit page and adjust fields.</li>
        </ol>
    </x-card>

    {{-- Examples --}}
    <x-card title="Concrete Examples" shadow separator>
        <p class="mb-4 text-base-content/80">
            Here are the four default templates provided by the system. Use them as reference when creating your own.
        </p>

        <details class="collapse collapse-arrow border border-base-300 bg-base-100 rounded-box mb-3">
            <summary class="collapse-title font-semibold text-lg">
                <x-badge value="Stamping" class="badge-primary" />
                Stamping Template
            </summary>
            <div class="collapse-content">
                <div class="overflow-x-auto">
                    <table class="table table-zebra">
                        <thead>
                            <tr>
                                <th>Section</th>
                                <th>Fields</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <strong>Visual Defect Check</strong>
                                    <div class="text-xs text-base-content/50">single row</div>
                                </td>
                                <td><code>is_defect</code> (boolean, required)</td>
                            </tr>
                            <tr>
                                <td>
                                    <strong>Jig / Spec Conformance</strong>
                                    <div class="text-xs text-base-content/50">single row</div>
                                </td>
                                <td><code>jig_spec_ok</code> (boolean, required)</td>
                            </tr>
                            <tr>
                                <td>
                                    <strong>Final Judgement</strong>
                                    <div class="text-xs text-base-content/50">single row</div>
                                </td>
                                <td>
                                    <code>manual_judgement</code> (enum: OK,NG,REPAIR, required)<br>
                                    <code>judgement_remarks</code> (text, optional)
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </details>

        <details class="collapse collapse-arrow border border-base-300 bg-base-100 rounded-box mb-3">
            <summary class="collapse-title font-semibold text-lg">
                <x-badge value="Station Spot" class="badge-secondary" />
                Station Spot Template
            </summary>
            <div class="collapse-content">
                <div class="overflow-x-auto">
                    <table class="table table-zebra">
                        <thead>
                            <tr>
                                <th>Section</th>
                                <th>Fields</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <strong>Hardware Measurements</strong>
                                    <div class="text-xs text-base-content/50">multi-row &middot; source: <code>part_hardware_mappings</code></div>
                                </td>
                                <td>
                                    <code>measurement_value</code> (numeric, required)
                                    <div class="text-xs text-base-content/50">auto-judge via <code>measurement_standard</code></div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p class="mt-2 text-sm text-base-content/70">
                    Each row is dynamically generated from the part's hardware mappings. The input label shows "Hardware Name (Part Number)" and the standard range is displayed beneath.
                </p>
            </div>
        </details>

        <details class="collapse collapse-arrow border border-base-300 bg-base-100 rounded-box mb-3">
            <summary class="collapse-title font-semibold text-lg">
                <x-badge value="Portable Spot" class="badge-accent" />
                Portable Spot Template
            </summary>
            <div class="collapse-content">
                <div class="overflow-x-auto">
                    <table class="table table-zebra">
                        <thead>
                            <tr>
                                <th>Section</th>
                                <th>Fields</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <strong>Tap Test Check</strong>
                                    <div class="text-xs text-base-content/50">single row</div>
                                </td>
                                <td><code>is_ok</code> (boolean, required)</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p class="mt-2 text-sm text-base-content/70">
                    Simple pass/fail after hammer-and-chisel tap test. No remarks field.
                </p>
            </div>
        </details>

        <details class="collapse collapse-arrow border border-base-300 bg-base-100 rounded-box mb-3">
            <summary class="collapse-title font-semibold text-lg">
                <x-badge value="Robot Spot" class="badge-warning" />
                Robot Spot Template
            </summary>
            <div class="collapse-content">
                <div class="overflow-x-auto">
                    <table class="table table-zebra">
                        <thead>
                            <tr>
                                <th>Section</th>
                                <th>Fields</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <strong>Visual & Jig Check</strong>
                                    <div class="text-xs text-base-content/50">single row</div>
                                </td>
                                <td><code>jig_ok</code> (boolean, optional)</td>
                            </tr>
                            <tr>
                                <td>
                                    <strong>Weld Length Measurement</strong>
                                    <div class="text-xs text-base-content/50">single row</div>
                                </td>
                                <td>
                                    <code>weld_length</code> (numeric, required)
                                    <div class="text-xs text-base-content/50">auto-judge via <code>weld_length_standard</code>, unit: mm</div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="mt-2 p-3 bg-warning/10 rounded-box text-sm">
                    <x-icon name="o-exclamation-triangle" class="h-4 w-4 inline text-warning" />
                    The <strong>Weld Length Measurement</strong> section is <strong>hidden</strong> when no weld length standard exists for the selected part + work station combination. The checker only sees the jig check in that case.
                </div>
            </div>
        </details>
    </x-card>

    {{-- Tips --}}
    <x-card title="Tips & Reminders" shadow separator>
        <ul class="space-y-3 text-base-content/80">
            <li class="flex items-start gap-2">
                <x-icon name="o-arrow-path" class="w-5 h-5 mt-0.5 shrink-0 text-info" />
                <span>Use <strong>move up / move down</strong> buttons on sections and fields to rearrange them. The order is reflected on the inspection form exactly as configured.</span>
            </li>
            <li class="flex items-start gap-2">
                <x-icon name="o-trash" class="w-5 h-5 mt-0.5 shrink-0 text-error" />
                <span>Deleting a field removes it permanently. Deleting a section removes all its fields too. Deleting a template removes everything — there is no undo.</span>
            </li>
            <li class="flex items-start gap-2">
                <x-icon name="o-eye" class="w-5 h-5 mt-0.5 shrink-0 text-success" />
                <span>Only <strong>active</strong> templates are used on inspection forms. Inactive templates are ignored by the system.</span>
            </li>
            <li class="flex items-start gap-2">
                <x-icon name="o-information-circle" class="w-5 h-5 mt-0.5 shrink-0 text-accent" />
                <span>For multi-row sections with <code>part_hardware_mappings</code>, rows are automatically generated from the part's assigned hardware — no manual row creation needed on the form.</span>
            </li>
        </ul>
    </x-card>

    <div class="flex justify-center pb-8">
        <x-button
            label="Back to Checklists"
            link="{{ route('checklists.index') }}"
            icon="o-arrow-left"
            class="btn-primary btn-outline"
        />
    </div>
</div>
