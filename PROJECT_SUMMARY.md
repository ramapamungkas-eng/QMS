# SME (Start-Middle-End) QMS — Project Summary

## Overview

A Quality Management System for physical manufacturing processes (Stamping and Welding). Every part is inspected at Start / Middle / End stages per shift. Checklist shape and judgement logic differ per work station type, so the schema is configurable rather than hardcoded per station.

---

## Stack

| Component | Version |
|---|---|
| PHP | 8.4 |
| Laravel Framework | v13 |
| Livewire | v4 (single-file page components) |
| Mary UI | latest (daisyUI + Tailwind v4) |
| Tailwind CSS | v4 |
| database | SQLite (dev) |
| Pest | v4 |
| Pint | v1 |

---

## Directory Structure

```
app/
├── Enums/
│   ├── InspectionStage.php     # start / middle / end
│   ├── JudgementResult.php     # ok / ng / repair
│   ├── MeasurementType.php     # torque / nugget
│   ├── Shift.php               # day / night
│   └── UserRole.php            # manager / leader_admin / checker
├── Http/
│   ├── Controllers/
│   │   └── Controller.php      # abstract base
│   └── Middleware/
│       ├── EnsureCanAccessProcess.php   # checkers scoped to their process
│       └── EnsureUserIsAdmin.php         # manager / leader_admin only
├── Models/
│   ├── ChecklistField.php
│   ├── ChecklistSection.php
│   ├── ChecklistTemplate.php
│   ├── HardwareType.php
│   ├── InspectionFieldValue.php         # has source() → PartHardwareMapping
│   ├── InspectionRecord.php
│   ├── MeasurementStandard.php
│   ├── Part.php                         # weldLengthStandards() hasMany (per work station)
│   ├── PartHardwareMapping.php
│   ├── PartWorkStationType.php
│   ├── Process.php
│   ├── StationType.php
│   ├── User.php
│   ├── WeldLengthStandard.php           # belongsTo(WorkStation)
│   └── WorkStation.php
├── Providers/
│   └── AppServiceProvider.php
├── Services/
│   ├── AutoJudgementService.php       # evaluates field values against rules
│   ├── ChecklistTemplateService.php    # resolves templates, types, routes
│   └── InspectionStatsService.php     # computes per-type stats from unified data
├── Support/
│   └── ShiftResolver.php       # resolves Day/Night + production_date
└── View/Components/
    └── AppBrand.php            # sidebar logo/brand
database/
├── factories/                  # 8 factories (mostly empty skeleton)
├── migrations/                 # 31 migrations
└── seeders/
    ├── DatabaseSeeder.php
    ├── MasterDataSeeder.php    # processes, stations, parts, hardware, standards
    ├── ManagerSeeder.php       # default manager user
    └── ChecklistTemplateSeeder.php  # per-type templates, sections, fields
resources/views/
├── layouts/
│   ├── app.blade.php           # main layout with sidebar navigation
│   └── empty.blade.php         # bare layout (login)
├── components/                 # (empty — no shared blade components yet)
└── pages/                      # Livewire page components
    ├── ⚡login.blade.php
    ├── ⚡index.blade.php        # dashboard homepage
    ├── users/
    │   ├── ⚡index.blade.php
    │   ├── ⚡create.blade.php
    │   └── ⚡edit.blade.php
    ├── parts/
    │   ├── ⚡index.blade.php
    │   ├── ⚡create.blade.php
    │   └── ⚡edit.blade.php         # weld length standards per work station
    ├── hardware/
    │   ├── ⚡index.blade.php
    │   ├── ⚡create.blade.php
    │   └── ⚡edit.blade.php
    ├── work-stations/
    │   ├── ⚡index.blade.php
    │   ├── ⚡create.blade.php
    │   └── ⚡edit.blade.php
    ├── checklists/
    │   ├── ⚡index.blade.php        # admin checklist template list
    │   ├── ⚡create.blade.php       # create template
    │   └── ⚡edit.blade.php         # sections & fields builder
    └── inspections/
        └── checklist/
            ├── ⚡index.blade.php     # generic daily board for any type
            └── ⚡create.blade.php    # generic create form for any type
routes/
└── web.php                    # all routes (no api.php)
tests/
├── Feature/ExampleTest.php     # skeleton
├── Unit/ExampleTest.php        # skeleton
├── Pest.php                    # RefreshDatabase trait
└── TestCase.php                # base test case
```

---

## Database Schema

### Master Data

**`processes`**
| Column | Type | Notes |
|---|---|---|
| id | integer (PK) | |
| name | varchar | `Stamping` or `Welding` |

**`work_station_types`** (replaces hardcoded `WorkStationType` enum)
| Column | Type | Notes |
|---|---|---|
| id | integer (PK) | |
| process_id | integer (FK→processes) | |
| slug | varchar (unique) | `stamping`, `station-spot`, `portable-spot`, `robot-spot` |
| name | varchar | display name |
| description | text | nullable |
| icon | varchar | Mary UI icon name |

**`work_stations`**
| Column | Type | Notes |
|---|---|---|
| id | integer (PK) | |
| process_id | integer (FK→processes) | |
| name | varchar | e.g. A1–A5, Fengyu (Stamping); SSW, PSW, RSW (Welding) |
| station_type_id | integer (FK→work_station_types) | replaces old `type` string column |

**`parts`**
| Column | Type | Notes |
|---|---|---|
| id | integer (PK) | |
| part_number | varchar (unique) | |
| part_name | varchar | |
| model | varchar | nullable |
| variant | varchar | nullable |
| image | varchar | nullable |

**`hardware_types`**
| Column | Type | Notes |
|---|---|---|
| id | integer (PK) | |
| part_number | varchar (unique) | hardware part number |
| part_name | varchar | |
| image | varchar | nullable |

**`part_work_station_types`**
| Column | Type | Notes |
|---|---|---|
| id | integer (PK) | |
| part_id | integer (FK→parts) | |
| station_type_id | integer (FK→work_station_types) | replaces old `work_station_type` string |
| *(unique: part_id + station_type_id)* | | |

### Configuration

**`part_hardware_mappings`** (Station Spot only)
| Column | Type | Notes |
|---|---|---|
| part_id | integer (FK→parts) | |
| hardware_type_id | integer (FK→hardware_types) | |
| measurement_type | varchar (enum) | `torque` or `nugget` |
| usage_qty | tinyint | quantity physically installed |
| *(unique: part_id + hardware_type_id + measurement_type)* | | |

**`measurement_standards`** (Station Spot only)
| Column | Type | Notes |
|---|---|---|
| part_hardware_mapping_id | integer (FK→part_hardware_mappings, unique) | |
| min_value | decimal(8,2) | |
| max_value | decimal(8,2) | |
| unit | varchar | |

**`weld_length_standards`** (Robot Spot only)
| Column | Type | Notes |
|---|---|---|
| part_id | integer (FK→parts) | |
| work_station_id | integer (FK→work_stations) | which Robot Spot station this applies to |
| min_length | decimal(8,2) | |
| max_length | decimal(8,2) | |
| unit | varchar | default `mm` |
| *(unique: part_id + work_station_id)* | | |

### Transactional

**`inspection_records`** (header)
| Column | Type | Notes |
|---|---|---|
| id | integer (PK) | |
| part_id | integer (FK→parts) | |
| work_station_id | integer (FK→work_stations) | |
| stage | varchar (enum) | `start`, `middle`, `end` |
| checker_id | integer (FK→users) | |
| checked_at | datetime | raw submit timestamp |
| shift | varchar (enum) | `day`, `night` (auto-resolved) |
| production_date | date | auto-calculated via ShiftResolver |
| *(indexed: work_station_id + production_date + shift)* | | |

### Configurable Checklist System (replaces per-type detail tables)

**`inspection_checklist_templates`** — one active template per workstation type
| Column | Type | Notes |
|---|---|---|
| id | integer (PK) | |
| station_type_id | integer (FK→work_station_types, unique) | replaces old `work_station_type` string |
| name | varchar | display name |
| active | boolean | soft on/off toggle |

**`inspection_checklist_sections`** — groups fields within a template
| Column | Type | Notes |
|---|---|---|
| template_id | integer (FK) | |
| label | varchar | e.g. "Visual Check", "Hardware Measurements" |
| order | tinyint | display order |
| allow_multiple | boolean | for multi-row data (Station Spot hardware) |
| source_type | varchar | nullable, e.g. `part_hardware_mappings` |

**`inspection_checklist_fields`** — individual checkpoints
| Column | Type | Notes |
|---|---|---|
| section_id | integer (FK) | |
| field_key | varchar | machine name (e.g. `is_defect`, `weld_length`) |
| label | varchar | display label |
| field_type | varchar | `boolean`, `numeric`, `enum`, `text` |
| options | json | nullable, enum values array |
| required | boolean | |
| order | tinyint | |
| has_auto_judge | boolean | enables auto OK/NG |
| auto_judge_source | varchar | `limits`, `measurement_standard`, `weld_length_standard` |
| min_value / max_value | decimal | for limits-based auto-judge |
| unit | varchar | display unit |

**`inspection_field_values`** — unified response storage (replaces 4 detail tables)
| Column | Type | Notes |
|---|---|---|
| inspection_record_id | integer (FK) | |
| field_id | integer (FK→inspection_checklist_fields) | |
| value | text | stored as text, cast at runtime |
| auto_judgement | varchar | nullable `ok`/`ng` |
| remarks | text | nullable |
| group_index | smallint | for multi-row sections (0 for single) |
| source_id | bigint | nullable FK (e.g. part_hardware_mapping_id) |

### Users & Auth

**`users`**
| Column | Type | Notes |
|---|---|---|
| id | integer (PK) | |
| name | varchar | |
| nik | varchar(16, unique) | login credential (no email auth) |
| whatsapp | varchar | nullable |
| role | varchar (enum) | `manager`, `leader_admin`, `checker` (cast to UserRole) |
| process_id | integer (FK→processes) | nullable; scopes checkers to a process |
| password | varchar | hashed |
| pin | varchar | hashed (6-digit) |
| profile_pic | varchar | nullable |

### Laravel System Tables

`cache`, `cache_locks`, `sessions`, `password_reset_tokens`, `jobs`, `job_batches`, `failed_jobs`, `migrations`

---

## Models (15)

| Model | Table | Key Relations |
|---|---|---|
| `User` | users | belongsTo(Process) |
| `Process` | processes | hasMany(WorkStation), belongsToMany(Part) |
| `WorkStation` | work_stations | belongsTo(Process), belongsTo(StationType) |
| `Part` | parts | hasMany(PartHardwareMapping), hasMany(WeldLengthStandard), hasMany(InspectionRecord), hasMany(PartWorkStationType) |
| `HardwareType` | hardware_types | hasMany(PartHardwareMapping) |
| `PartHardwareMapping` | part_hardware_mappings | belongsTo(Part), belongsTo(HardwareType), hasOne(MeasurementStandard) |
| `MeasurementStandard` | measurement_standards | belongsTo(PartHardwareMapping) |
| `WeldLengthStandard` | weld_length_standards | belongsTo(Part), belongsTo(WorkStation) |
| `PartWorkStationType` | part_work_station_types | belongsTo(Part), belongsTo(StationType) |
| `StationType` | work_station_types | belongsTo(Process), hasMany(WorkStation), hasMany(ChecklistTemplate) |
| `InspectionRecord` | inspection_records | belongsTo(Part), belongsTo(WorkStation), belongsTo(checker), hasMany(fieldValues) |
| `ChecklistTemplate` | inspection_checklist_templates | belongsTo(StationType), hasMany(sections), scope active() |
| `ChecklistSection` | inspection_checklist_sections | belongsTo(template), hasMany(fields) |
| `ChecklistField` | inspection_checklist_fields | belongsTo(section) |
| `InspectionFieldValue` | inspection_field_values | belongsTo(record), belongsTo(field), belongsTo(source → PartHardwareMapping) |

---

## Enums (5)

| Enum | Values | Key Methods |
|---|---|---|
| `UserRole` | Manager, LeaderAdmin, Checker | `label()`, `description()` |
| `InspectionStage` | Start, Middle, End | `label()`, `description()` |
| `JudgementResult` | Ok, Ng, Repair | `label()`, `badgeClass()` |
| `MeasurementType` | Torque, Nugget | `label()`, `defaultUnit()` |
| `Shift` | Day, Night | `label()` |

---

## Routes

All routes in `routes/web.php` (no API routes).

### Guest
| Method | URI | Name | Component |
|---|---|---|---|
| GET | `/login` | `login` | pages::login |
| GET | `/logout` | `logout` | closure |

### Authenticated (middleware: `auth`)
| Method | URI | Name | Component |
|---|---|---|---|
| GET | `/` | (home) | pages::index |

### Inspections (prefix: `/inspections`) — routes generated dynamically from `work_station_types` table
| Method | URI | Name | Middleware | Component |
|---|---|---|---|---|
| GET | `/inspections/{type}` | `inspections.{type}.index` | EnsureCanAccessProcess | pages::inspections.checklist.index |
| GET | `/inspections/{type}/create` | `inspections.{type}.create` | EnsureCanAccessProcess | pages::inspections.checklist.create |

All station types (stamping, station-spot, portable-spot, robot-spot) share the same generic Livewire page components. Routes are generated at boot time from `work_station_types` rows, and process name is resolved dynamically from `$stationType->process->name`.

### Admin (middleware: `EnsureUserIsAdmin`)
| Method | URI | Name | Component |
|---|---|---|---|
| GET | `/users` | `users.index` | pages::users.index |
| GET | `/users/create` | `users.create` | pages::users.create |
| GET | `/users/{user}/edit` | `users.edit` | pages::users.edit |
| GET | `/hardware` | `hardware.index` | pages::hardware.index |
| GET | `/hardware/create` | `hardware.create` | pages::hardware.create |
| GET | `/hardware/{hardwareType}/edit` | `hardware.edit` | pages::hardware.edit |
| GET | `/parts` | `parts.index` | pages::parts.index |
| GET | `/parts/create` | `parts.create` | pages::parts.create |
| GET | `/parts/{part}/edit` | `parts.edit` | pages::parts.edit |
| GET | `/work-stations` | `work-stations.index` | pages::work-stations.index |
| GET | `/work-stations/create` | `work-stations.create` | pages::work-stations.create |
| GET | `/work-stations/{workStation}/edit` | `work-stations.edit` | pages::work-stations.edit |
| GET | `/checklists` | `checklists.index` | pages::checklists.index |
| GET | `/checklists/create` | `checklists.create` | pages::checklists.create |
| GET | `/checklists/{template}/edit` | `checklists.edit` | pages::checklists.edit |

---

## Middleware

| Middleware | Purpose |
|---|---|
| `EnsureUserIsAdmin` | Aborts 403 if role is not Manager or LeaderAdmin |
| `EnsureCanAccessProcess` | Accepts a process name param. Manager/LeaderAdmin pass through; Checkers must match their `process_id` |

---

## Shift Logic (`ShiftResolver`)

```
07:30–20:00   → Day shift,  production_date = today
20:00–00:00   → Night shift, production_date = today
00:00–07:30   → Night shift, production_date = yesterday
```

- `production_date` is auto-calculated server-side on inspection record creation.
- `checked_at` always stores the true submit timestamp (audit trail).

---

## Inspection Workflow

1. **Checker** logs in with NIK + password.
2. Dashboard shows their process's inspection types.
3. Opens **New Inspection** form — selects part, stage, and work station (auto-selected when only one exists for the type).
4. Fills type-specific checklist (see below).
5. Submits — record is **final immediately** (no approval step).
6. **Index board** shows daily production matrix: parts × stages (S/M/E) × shifts, with colour-coded status badges and clickable history modal.

### Per-Type Checklist Logic

| Type | Fields | Judgement |
|---|---|---|
| **Stamping** | Visual defect? (Y/N), Jig/Spec OK? (Y/N), Manual judgement (OK/NG/REPAIR), Remarks | Manual enum (OK/NG/REPAIR). Stage-level overall: enum → boolean fallback. Detail: enum values show OK/NG/REPAIR badges; booleans show Yes/No. |
| **Station Spot** | Measurement value per hardware mapping (torque or nugget) | Auto (against `measurement_standards`). Detail rows show hardware type label + standard range. |
| **Portable Spot** | Tap test pass? (Y/N) | Manual boolean. Stage-level overall: boolean fallback (`1` → OK, `0` → NG). Detail: value shows Yes/No, result derived from boolean. |
| **Robot Spot** | Jig OK? (Y/N). Weld length measurement — only shown when a standard exists for this part+work_station. | Weld length: auto (against `weld_length_standards`). Jig: manual boolean. When no standard exists, weld length section is hidden entirely. Stage-level overall: auto-judge → boolean fallback. |

### Overall Judgement Precedence (`overallJudgementFromValues`)

For stage badges, the overall result is determined in this order:
1. **Auto-judged fields** — if any field has `has_auto_judge`, all must be OK for OK result; any NG → overall NG
2. **Enum fields** — if no auto-judged fields, check `manual_judgement` (OK/NG/REPAIR)
3. **Boolean fields** — if no enum either, all booleans `'1'` → OK, any `'0'` → NG

### Detail Result Derivation

The inspection history modal shows per-field results:
- **Auto-judged**: shows the auto_judgement badge (OK/NG)
- **Enum fields** (`manual_judgement`): derives OK/NG/REPAIR from value
- **Boolean fields**: derives from value with field-key awareness — `is_defect` has inverted logic (`'0'` = OK, `'1'` = NG); all other booleans use standard logic (`'1'` = OK, `'0'` = NG)
- **Numeric fields** without auto_judge: shows `—`
- **Text fields**: shows `—`

---

## Roles & Permissions

| Role | Access |
|---|---|
| Manager | Full access — all master data, users, all inspection reports |
| Leader/Admin | Manage measurement standards, add parts, manage users |
| Checker | Input inspection records only; scoped to single process |

---

## Current Build Status

### Complete
- 31 migrations covering all tables (including checklist tables, station type FK migration + cleanup, weld_length_standards work_station_id)
- 15 Eloquent models with casts & relationships
- 5 enums with helper methods
- All routes with auth/process/admin middleware
- **Hardcoded `WorkStationType` enum replaced with `work_station_types` DB table** — new station types can be added via UI
- **Configurable checklist system** — templates, sections, fields define per-type forms dynamically
- **Generic Livewire components** — both create form and daily index board driven by template definition
- **3 Services** — ChecklistTemplateService, AutoJudgementService, InspectionStatsService
- **Migration from per-type detail tables** — old tables dropped; data in `inspection_field_values`
- Admin CRUD: Users, Parts, Hardware Types, Work Stations
- **Checklist management UI** — admin can create/edit templates, sections, fields with modal builder
- Part edit page: hardware mapping CRUD (Station Spot) + per-work-station weld length standard CRUD (Robot Spot)
- Dashboard homepage with role-aware cards and today's summary
- Login page (NIK + password) + logout
- ShiftResolver utility
- Seeders: MasterDataSeeder, ManagerSeeder, ChecklistTemplateSeeder
- **Weld length standards are per-work-station** (`part_id + work_station_id` unique), editable on the parts edit page via modal
- **Boolean field handling** — index page derives stage-level overall judgement and per-field detail results with correct semantics (inverted for `is_defect`, standard for others)
- **Hardware info in index** — history modal shows hardware type name + part number beneath field label, and standard range on create form
- **Conditional weld length** — Robot Spot inspection hides the Weld Length Measurement section when no standard exists for the selected part+work_station
- **Reports page** — Livewire SFC with styled Excel export via GenerateReport job, Export model, progress tracking, download route
- **Auto-select work station** — create/inspection pages hide the work station selector when only one option exists (Stamping shows A1-A5/Fengyu; welding types auto-select SSW/PSW/RSW)
- **MasterDataSeeder** — stamping stations A1–A5 + Fengyu; welding stations SSW, PSW, RSW; dedup logic for idempotent reruns
- **Route parameter fix** — work-stations edit route uses `{workStation}` to match Livewire mount parameter

### Not Yet Built
- Meaningful tests (2 skeleton tests only; 6/8 factories empty)
- Git repository
- Notifications (email/WhatsApp for NG results)
- Notifications (email/WhatsApp for NG results)
- Event/listener infrastructure
- Cache/queue job setup
- `process_id` field on user create/edit forms
- API routes
- Image upload storage config
