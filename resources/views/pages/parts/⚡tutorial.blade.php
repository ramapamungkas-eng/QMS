<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Layout('layouts.app')]
#[Title('Parts Management Tutorial')]
class extends Component
{
    //
}; ?>

<div class="max-w-4xl mx-auto space-y-6">
    <x-header
        title="Parts Management Tutorial"
        subtitle="Learn how to create and manage parts with their hardware mappings and standards."
        separator
    >
        <x-slot:actions>
            <x-button
                label="Back to Parts"
                link="{{ route('parts.index') }}"
                icon="o-arrow-left"
                class="btn-soft"
                responsive
            />
        </x-slot:actions>
    </x-header>

    {{-- Overview --}}
    <x-card title="What is a Part?" shadow>
        <p class="text-base-content/80">
            A <strong>part</strong> in the QMS represents a single physical component that flows through one or more work station types (Stamping, Station Spot, Portable Spot, Robot Spot). Each part has a unique part number and can be associated with multiple station types.
        </p>
    </x-card>

    {{-- Creating a Part --}}
    <x-card title="Creating a Part" shadow separator>
        <p class="mb-4 text-base-content/80">
            Click <strong>New Part</strong> from the Parts list page to register a new part.
        </p>

        <div class="overflow-x-auto">
            <table class="table table-zebra">
                <thead>
                    <tr>
                        <th class="w-36">Field</th>
                        <th class="w-20">Required</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="font-medium">Part number</td>
                        <td><x-badge value="Yes" class="badge-error badge-sm" /></td>
                        <td>Unique identifier. Cannot be duplicated.</td>
                    </tr>
                    <tr>
                        <td class="font-medium">Part name</td>
                        <td><x-badge value="Yes" class="badge-error badge-sm" /></td>
                        <td>Display name for the part.</td>
                    </tr>
                    <tr>
                        <td class="font-medium">Model</td>
                        <td><x-badge value="No" class="badge-ghost badge-sm" /></td>
                        <td>Model name for identification.</td>
                    </tr>
                    <tr>
                        <td class="font-medium">Variant</td>
                        <td><x-badge value="No" class="badge-ghost badge-sm" /></td>
                        <td>Variant name for identification.</td>
                    </tr>
                    <tr>
                        <td class="font-medium">Photo</td>
                        <td><x-badge value="No" class="badge-ghost badge-sm" /></td>
                        <td>JPEG or PNG, up to 2MB. Helps identify the part visually.</td>
                    </tr>
                    <tr>
                        <td class="font-medium">Station Types</td>
                        <td><x-badge value="Yes" class="badge-error badge-sm" /></td>
                        <td>Select at least one station type where this part is inspected.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <h3 class="font-semibold mt-6 mb-2">Station Types</h3>
        <p class="text-sm text-base-content/80">
            Each part must be assigned to at least one station type. The station types determine:
        </p>
        <ul class="list-disc list-inside mt-2 space-y-1 text-sm text-base-content/70">
            <li>Which inspection forms are available for this part</li>
            <li>Which sections appear on the edit page (e.g. hardware mappings for Station Spot, weld length standards for Robot Spot)</li>
        </ul>
        <p class="mt-2 text-sm text-base-content/70">
            A part can span multiple station types — for example, a stamped body panel may also be inspected at Station Spot welding.
        </p>

        <div class="mt-4 p-4 bg-base-200 rounded-box text-sm">
            <x-icon name="o-light-bulb" class="h-4 w-4 inline" />
            After saving, you're redirected to the <strong>edit page</strong> to configure hardware mappings and standards.
        </div>
    </x-card>

    {{-- Editing a Part --}}
    <x-card title="Editing a Part" shadow separator>
        <p class="mb-4 text-base-content/80">
            The edit page is organized into collapsible sections that appear based on the part's assigned station types.
        </p>

        <h3 class="font-semibold mb-2">1. Identity</h3>
        <p class="text-sm text-base-content/70 mb-4">
            Update basic information and toggle which station types the part belongs to. Toggling a station type on/off controls what you can configure below.
        </p>

        <h3 class="font-semibold mb-2">2. Hardware Mappings</h3>
        <p class="text-sm text-base-content/70 mb-1">
            Shown only when <x-badge value="Station Spot" class="badge-accent badge-sm" /> is selected as a station type.
        </p>
        <p class="text-sm text-base-content/70 mb-3">
            A hardware mapping links a hardware type (nut/bolt) to the part with a specific measurement type and tolerance standard. Each mapping represents a physical piece of hardware installed on the part.
        </p>

        <div class="overflow-x-auto mb-4">
            <table class="table table-zebra">
                <thead>
                    <tr>
                        <th>Field</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="font-medium">Hardware</td>
                        <td>Select from registered hardware types (e.g. M6 Nut).</td>
                    </tr>
                    <tr>
                        <td class="font-medium">Measurement type</td>
                        <td><code>Torque</code> or <code>Nugget</code> — determines the measurement method and default unit.</td>
                    </tr>
                    <tr>
                        <td class="font-medium">Usage qty</td>
                        <td>How many are physically installed. The checker still enters one representative measurement value.</td>
                    </tr>
                    <tr>
                        <td class="font-medium">Min / Max value</td>
                        <td>Acceptable range for auto-judgement on inspection forms.</td>
                    </tr>
                    <tr>
                        <td class="font-medium">Unit</td>
                        <td>Display unit (defaults based on measurement type, but can be overridden).</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="p-4 bg-info/10 rounded-box text-sm mb-4">
            <x-icon name="o-information-circle" class="h-4 w-4 inline text-info" />
            The pair <strong>(hardware type + measurement type)</strong> must be unique per part — you cannot add the same hardware with the same measurement type twice.
        </div>

        <h3 class="font-semibold mb-2">3. Weld Length Standards</h3>
        <p class="text-sm text-base-content/70 mb-1">
            Shown only when <x-badge value="Robot Spot" class="badge-warning badge-sm" /> is selected as a station type.
        </p>
        <p class="text-sm text-base-content/70 mb-3">
            Defines the acceptable weld length range for a specific work station. Since standards can differ per work station, each Robot Spot station can have its own min/max values.
        </p>

        <div class="overflow-x-auto mb-4">
            <table class="table table-zebra">
                <thead>
                    <tr>
                        <th>Field</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="font-medium">Work Station</td>
                        <td>Select a Robot Spot work station (e.g. "RS-1").</td>
                    </tr>
                    <tr>
                        <td class="font-medium">Min / Max length</td>
                        <td>Acceptable weld length range.</td>
                    </tr>
                    <tr>
                        <td class="font-medium">Unit</td>
                        <td>Display unit (defaults to mm).</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="p-4 bg-warning/10 rounded-box text-sm">
            <x-icon name="o-exclamation-triangle" class="h-4 w-4 inline text-warning" />
            If a part at a Robot Spot work station has <strong>no weld length standard</strong> configured, the inspection form hides the Weld Length Measurement section entirely — the checker only sees the visual/jig check.
        </div>
    </x-card>

    {{-- Part Lifecycle --}}
    <x-card title="Part Lifecycle Across Station Types" shadow separator>
        <p class="mb-4 text-base-content/80">
            A single part can be inspected at multiple station types. Here's a typical flow:
        </p>

        <div class="flex flex-wrap items-center gap-3 py-4">
            <div class="rounded-xl border border-base-300 bg-base-100 px-5 py-3 text-sm font-medium">
                Part created
            </div>
            <x-icon name="o-arrow-long-right" class="h-5 w-5 text-base-content/30" />

            <div class="rounded-xl border border-base-300 bg-info/10 px-5 py-3 text-sm font-medium">
                Stamping
            </div>
            <x-icon name="o-arrow-long-right" class="h-5 w-5 text-base-content/30" />

            <div class="rounded-xl border border-base-300 bg-accent/10 px-5 py-3 text-sm font-medium">
                Station Spot
            </div>
            <x-icon name="o-arrow-long-right" class="h-5 w-5 text-base-content/30" />

            <div class="rounded-xl border border-base-300 bg-warning/10 px-5 py-3 text-sm font-medium">
                Portable Spot
            </div>
            <x-icon name="o-arrow-long-right" class="h-5 w-5 text-base-content/30" />

            <div class="rounded-xl border border-base-300 bg-secondary/10 px-5 py-3 text-sm font-medium">
                Robot Spot
            </div>
        </div>

        <p class="text-sm text-base-content/70 mt-4">
            Each station type's inspection is recorded independently. The <strong>same part row</strong> is used throughout — no duplicate part rows per process.
        </p>
    </x-card>

    {{-- Seeded Sample Parts --}}
    <x-card title="Seeded Sample Parts" shadow separator>
        <p class="mb-4 text-base-content/80">
            The system comes pre-loaded with sample parts. Use them as reference.
        </p>

        <div class="overflow-x-auto">
            <table class="table table-zebra">
                <thead>
                    <tr>
                        <th>Part Number</th>
                        <th>Name</th>
                        <th>Station Types</th>
                        <th>Weld Standard</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="font-mono">PR-1001</td>
                        <td>Body Panel Front</td>
                        <td>
                            <x-badge value="Stamping" class="badge-info badge-sm" />
                            <x-badge value="Station Spot" class="badge-accent badge-sm" />
                            <x-badge value="Portable Spot" class="badge-warning badge-sm" />
                            <x-badge value="Robot Spot" class="badge-secondary badge-sm" />
                        </td>
                        <td>10–15 mm</td>
                    </tr>
                    <tr>
                        <td class="font-mono">PR-1002</td>
                        <td>Body Panel Rear</td>
                        <td>
                            <x-badge value="Stamping" class="badge-info badge-sm" />
                            <x-badge value="Station Spot" class="badge-accent badge-sm" />
                            <x-badge value="Portable Spot" class="badge-warning badge-sm" />
                            <x-badge value="Robot Spot" class="badge-secondary badge-sm" />
                        </td>
                        <td>10–15 mm</td>
                    </tr>
                    <tr>
                        <td class="font-mono">PR-1003</td>
                        <td>Door Inner LH</td>
                        <td>
                            <x-badge value="Stamping" class="badge-info badge-sm" />
                            <x-badge value="Station Spot" class="badge-accent badge-sm" />
                            <x-badge value="Portable Spot" class="badge-warning badge-sm" />
                            <x-badge value="Robot Spot" class="badge-secondary badge-sm" />
                        </td>
                        <td>8–12 mm</td>
                    </tr>
                    <tr>
                        <td class="font-mono">PR-1004</td>
                        <td>Door Inner RH</td>
                        <td>
                            <x-badge value="Stamping" class="badge-info badge-sm" />
                            <x-badge value="Station Spot" class="badge-accent badge-sm" />
                            <x-badge value="Portable Spot" class="badge-warning badge-sm" />
                            <x-badge value="Robot Spot" class="badge-secondary badge-sm" />
                        </td>
                        <td>8–12 mm</td>
                    </tr>
                    <tr>
                        <td class="font-mono">PR-2001</td>
                        <td>Cross Member Assembly</td>
                        <td><x-badge value="Station Spot" class="badge-accent badge-sm" /></td>
                        <td class="text-base-content/40">—</td>
                    </tr>
                    <tr>
                        <td class="font-mono">PR-2002</td>
                        <td>Bracket Support</td>
                        <td><x-badge value="Robot Spot" class="badge-secondary badge-sm" /></td>
                        <td>5–10 mm</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </x-card>

    {{-- Tips --}}
    <x-card title="Tips & Reminders" shadow separator>
        <ul class="space-y-3 text-base-content/80">
            <li class="flex items-start gap-2">
                <x-icon name="o-hashtag" class="w-5 h-5 mt-0.5 shrink-0 text-info" />
                <span>Part numbers must be <strong>unique</strong>. Choose a consistent naming convention (e.g. PR-XXXX).</span>
            </li>
            <li class="flex items-start gap-2">
                <x-icon name="o-trash" class="w-5 h-5 mt-0.5 shrink-0 text-error" />
                <span>Deleting a part removes all its hardware mappings and standards. Inspection records remain for audit purposes.</span>
            </li>
            <li class="flex items-start gap-2">
                <x-icon name="o-cube" class="w-5 h-5 mt-0.5 shrink-0 text-success" />
                <span>A part can be associated with <strong>multiple station types</strong> — the edit page shows only the relevant configuration sections based on your selections.</span>
            </li>
            <li class="flex items-start gap-2">
                <x-icon name="o-arrows-right-left" class="w-5 h-5 mt-0.5 shrink-0 text-accent" />
                <span>Hardware mappings are <strong>Station Spot only</strong>. Weld length standards are <strong>Robot Spot only</strong>. Other station types need no additional configuration.</span>
            </li>
            <li class="flex items-start gap-2">
                <x-icon name="o-scale" class="w-5 h-5 mt-0.5 shrink-0 text-warning" />
                <span>Weld length standards are <strong>per work station</strong>, not global — each Robot Spot station can have different tolerances for the same part.</span>
            </li>
        </ul>
    </x-card>

    <div class="flex justify-center pb-8">
        <x-button
            label="Back to Parts"
            link="{{ route('parts.index') }}"
            icon="o-arrow-left"
            class="btn-primary btn-outline"
        />
    </div>
</div>
