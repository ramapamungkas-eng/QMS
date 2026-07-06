# Creating Checklist Templates

A **checklist template** defines the inspection form layout for a given work station type (Stamping, Station Spot, Portable Spot, or Robot Spot). Each template is composed of **sections** that group related **fields** together.

---

## Access

Navigate to **Master Data → Checklists** (requires Manager or Leader/Admin role).

From the checklist list, click **New template** to create one, or **Tutorial** to read this guide.

---

## Step 1: Create a Template

Click **New template** and fill in:

| Field | Description |
|---|---|
| **Station Type** | Which work station type this template is for. Each station type can have **one active template** at a time. |
| **Template Name** | A descriptive name, e.g. "Welding Station Spot Checklist". |
| **Active** | Toggle to enable/disable. Only active templates are used when checkers submit inspections. |

After saving, you land on the edit page where you add sections and fields.

---

## Step 2: Add Sections

A **section** groups related inspection points. Click **Add section** to create one.

| Setting | Description |
|---|---|
| **Label** | Section heading shown on the inspection form. |
| **Allow Multiple** | Enable for repeating rows (e.g. Station Spot's per-hardware measurements). Disabled for single-row sections like Visual Checks. |
| **Source Type** | Required when **Allow Multiple** is on. Currently only `part_hardware_mappings` is supported — it dynamically creates one row per hardware mapping assigned to the selected part. |

### Section Examples

| Section Type | Allow Multiple | Source Type | Example |
|---|---|---|---|
| Visual checks | No | (none) | Stamping's "Visual Defect Check" |
| Hardware measurements | Yes | `part_hardware_mappings` | Station Spot's hardware rows |
| Single measurement | No | (none) | Robot Spot's weld length |

Use the **move up/down** buttons to reorder sections.

---

## Step 3: Add Fields

Within each section, click **Add field** to create an inspection point. Each field has these settings:

### Basic Settings

| Setting | Description |
|---|---|
| **Field Key** | Machine name (snake_case). Used internally. Must be unique within the section. Examples: `is_defect`, `weld_length`, `manual_judgement`. |
| **Label** | Display text shown to the checker on the inspection form. |
| **Field Type** | Controls the input widget. See below for type details. |
| **Order** | Display order within the section (auto-incremented). |
| **Required** | If checked, the form enforces that a value must be entered. |

### Field Types

| Type | Widget | Use Case | Example |
|---|---|---|---|
| **boolean** | Yes / No radio cards | Pass/fail checks | "Visual Defect Present?" |
| **numeric** | Number input | Measurements | Weld length (mm), torque value |
| **enum** | Radio cards with options | Choice from predefined values | Manual judgement: OK / NG / REPAIR |
| **text** | Textarea | Free-text notes | Remarks, comments |

For **enum** fields, enter the options as a comma-separated list in the **Options** field. Example: `OK,NG,REPAIR`

### Auto-Judgement

Enable **Has Auto Judge** to have the system automatically determine OK/NG for numeric fields. Then choose the source:

| Source | Description | Applies To |
|---|---|---|
| **limits** | Compares the value against the field's own **Min Value** / **Max Value**. | Simple ranges (e.g. temperature 20–30°C) |
| **measurement_standard** | Looks up the standard from `measurement_standards` via the part's hardware mapping. Used when different parts have different standards for the same hardware. | Station Spot torque/nugget values |
| **weld_length_standard** | Looks up min/max from `weld_length_standards` by `(part_id, work_station_id)`. | Robot Spot weld length |

**Unit**: Display label shown after the value (e.g. `mm`, `Nm`).

---

## Step 4: Save & Test

1. Each section and field saves independently via inline modals.
2. After setting up the template, visit the corresponding inspection page:
   - `/inspections/{slug}` to see the board
   - `/inspections/{slug}/create` to test the form
3. If the form doesn't look right, go back to the edit page and adjust fields.

---

## Concrete Examples

### Stamping Template

| Section | Fields |
|---|---|
| **Visual Defect Check** (single row) | `is_defect` (boolean, required) |
| **Jig / Spec Conformance** (single row) | `jig_spec_ok` (boolean, required) |
| **Final Judgement** (single row) | `manual_judgement` (enum: OK,NG,REPAIR, required), `judgement_remarks` (text, optional) |

### Station Spot Template

| Section | Fields |
|---|---|
| **Hardware Measurements** (multi-row, source: `part_hardware_mappings`) | `measurement_value` (numeric, required, auto-judge via `measurement_standard`) |

Each row is dynamically generated from the part's hardware mappings. The input label shows "Hardware Name (Part Number)" and the standard range is displayed beneath.

### Portable Spot Template

| Section | Fields |
|---|---|
| **Tap Test Check** (single row) | `is_ok` (boolean, required) |

Simple pass/fail after hammer-and-chisel tap test. No remarks field.

### Robot Spot Template

| Section | Fields |
|---|---|
| **Visual & Jig Check** (single row) | `jig_ok` (boolean, optional) |
| **Weld Length Measurement** (single row) | `weld_length` (numeric, required, auto-judge via `weld_length_standard`, unit: `mm`) |

The **Weld Length Measurement** section is **hidden** when no weld length standard exists for the selected part + work station combination. The checker only sees the jig check in that case.

---

## Reordering

- Use **move up / move down** buttons on sections and fields to rearrange them.
- The order is reflected on the inspection form exactly as configured.

---

## Deleting

- Deleting a field removes it from the form permanently.
- Deleting a section removes all its fields too.
- Deleting a template removes everything — there is no undo.

---

## How It Renders

When a checker opens the inspection form, `ChecklistTemplateService::forType()` loads the active template with all sections and fields. The form renders each section as a card, and each field as its appropriate input type (boolean → radio cards, numeric → number input, enum → radio cards, text → textarea).

Multi-row sections (`allow_multiple = true`, `source_type = part_hardware_mappings`) dynamically create one row per hardware mapping, labeled with the hardware name and part number.
