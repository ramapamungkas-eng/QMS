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
│   ├── UserRole.php            # manager / leader_admin / checker
│   └── WorkStationType.php     # stamping / station_spot / portable_spot / robot_spot
├── Http/
│   ├── Controllers/
│   │   └── Controller.php      # abstract base
│   └── Middleware/
│       ├── EnsureCanAccessProcess.php   # checkers scoped to their process
│       └── EnsureUserIsAdmin.php         # manager / leader_admin only
├── Models/                     # 14 Eloquent models
├── Providers/
│   └── AppServiceProvider.php
├── Support/
│   └── ShiftResolver.php       # resolves Day/Night + production_date
└── View/Components/
    └── AppBrand.php            # sidebar logo/brand
database/
├── factories/                  # 8 factories (mostly empty skeleton)
├── migrations/                 # 23 migrations
└── seeders/
    ├── DatabaseSeeder.php
    ├── MasterDataSeeder.php    # processes, stations, part_work_station_types
    └── ManagerSeeder.php       # default manager user
resources/views/
├── layouts/
│   ├── app.blade.php           # main layout with sidebar navigation
│   └── empty.blade.php         # bare layout (login)
├── components/                 # (empty — no shared blade components yet)
└── pages/                      # Livewire page components
    ├── ⚡login.blade.php
    ├── ⚡index.blade.php        # dashboard homepage
    ├── ⚡index.blade.php        # (root — not a route)
    ├── users/
    │   ├── ⚡index.blade.php
    │   ├── ⚡create.blade.php
    │   └── ⚡edit.blade.php
    ├── parts/
    │   ├── ⚡index.blade.php
    │   ├── ⚡create.blade.php
    │   └── ⚡edit.blade.php
    ├── hardware/
    │   ├── ⚡index.blade.php
    │   ├── ⚡create.blade.php
    │   └── ⚡edit.blade.php
    ├── work-stations/
    │   ├── ⚡index.blade.php
    │   ├── ⚡create.blade.php
    │   └── ⚡edit.blade.php
    └── inspections/
        ├── stamping/
        │   ├── ⚡index.blade.php
        │   └── ⚡create.blade.php
        ├── station-spot/
        │   ├── ⚡index.blade.php
        │   └── ⚡create.blade.php
        ├── portable-spot/
        │   ├── ⚡index.blade.php
        │   └── ⚡create.blade.php
        └── robot-spot/
            ├── ⚡index.blade.php
            └── ⚡create.blade.php
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

**`work_stations`**
| Column | Type | Notes |
|---|---|---|
| id | integer (PK) | |
| process_id | integer (FK→processes) | |
| name | varchar | e.g. B1, B2, Fengyu, Station Spot |
| type | varchar (enum) | `stamping`, `station_spot`, `portable_spot`, `robot_spot` |

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
| work_station_type | varchar | which station type this part routes through |
| *(unique: part_id + work_station_type)* | | |

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
| part_id | integer (FK→parts, unique) | |
| min_length | decimal(8,2) | |
| max_length | decimal(8,2) | |
| unit | varchar | default `mm` |

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

**`stamping_inspection_details`** (1:1 with inspection_record)
| Column | Type | Notes |
|---|---|---|
| inspection_record_id | integer (FK, unique) | |
| is_defect | boolean | visual defect present? |
| defect_remarks | text | nullable |
| jig_spec_ok | boolean | jig/spec conforms? |
| jig_remarks | text | nullable |
| manual_judgement | varchar (enum) | `ok`, `ng`, `repair` |
| judgement_remarks | text | nullable |

**`station_spot_inspection_details`** (1:N with inspection_record)
| Column | Type | Notes |
|---|---|---|
| inspection_record_id | integer (FK) | |
| part_hardware_mapping_id | integer (FK→part_hardware_mappings) | |
| measurement_value | decimal(10,2) | numeric reading |
| auto_judgement | varchar (enum) | `ok` or `ng` (auto-calculated against standards) |
| remarks | text | nullable |

**`portable_spot_inspection_details`** (1:1 with inspection_record)
| Column | Type | Notes |
|---|---|---|
| inspection_record_id | integer (FK, unique) | |
| is_ok | boolean | pass/fail after tap test |
| remarks | text | nullable (required if fail) |

**`robot_spot_inspection_details`** (1:1 with inspection_record)
| Column | Type | Notes |
|---|---|---|
| inspection_record_id | integer (FK, unique) | |
| weld_length | decimal(8,2) | measured length |
| auto_judgement | varchar (enum) | `ok` or `ng` (auto-calculated) |
| jig_ok | boolean | nullable |
| jig_remarks | text | nullable |

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

## Models (14)

| Model | Table | Key Relations |
|---|---|---|
| `User` | users | belongsTo(Process) |
| `Process` | processes | hasMany(WorkStation), belongsToMany(Part) |
| `WorkStation` | work_stations | belongsTo(Process) |
| `Part` | parts | hasMany(PartHardwareMapping), hasOne(WeldLengthStandard), hasMany(InspectionRecord), hasMany(PartWorkStationType) |
| `HardwareType` | hardware_types | hasMany(PartHardwareMapping) |
| `PartHardwareMapping` | part_hardware_mappings | belongsTo(Part), belongsTo(HardwareType), hasOne(MeasurementStandard) |
| `MeasurementStandard` | measurement_standards | belongsTo(PartHardwareMapping) |
| `WeldLengthStandard` | weld_length_standards | belongsTo(Part) |
| `PartWorkStationType` | part_work_station_types | belongsTo(Part) |
| `InspectionRecord` | inspection_records | belongsTo(Part), belongsTo(WorkStation), belongsTo(checker), hasOne(StampingDetail), hasMany(StationSpotDetails), hasOne(PortableSpotDetail), hasOne(RobotSpotDetail) |
| `StampingInspectionDetail` | stamping_inspection_details | belongsTo(InspectionRecord) |
| `StationSpotInspectionDetail` | station_spot_inspection_details | belongsTo(InspectionRecord), belongsTo(PartHardwareMapping) |
| `PortableSpotInspectionDetail` | portable_spot_inspection_details | belongsTo(InspectionRecord) |
| `RobotSpotInspectionDetail` | robot_spot_inspection_details | belongsTo(InspectionRecord) |

---

## Enums (6)

| Enum | Values | Key Methods |
|---|---|---|
| `UserRole` | Manager, LeaderAdmin, Checker | `label()`, `description()` |
| `WorkStationType` | Stamping, StationSpot, PortableSpot, RobotSpot | `label()`, `description()`, `icon()` |
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

### Inspections (prefix: `/inspections`)
| Method | URI | Name | Middleware | Component |
|---|---|---|---|---|
| GET | `/inspections/stamping` | `inspections.stamping.index` | process:Stamping | pages::inspections.stamping.index |
| GET | `/inspections/stamping/create` | `inspections.stamping.create` | process:Stamping | pages::inspections.stamping.create |
| GET | `/inspections/station-spot` | `inspections.station-spot.index` | process:Welding | pages::inspections.station-spot.index |
| GET | `/inspections/station-spot/create` | `inspections.station-spot.create` | process:Welding | pages::inspections.station-spot.create |
| GET | `/inspections/portable-spot` | `inspections.portable-spot.index` | process:Welding | pages::inspections.portable-spot.index |
| GET | `/inspections/portable-spot/create` | `inspections.portable-spot.create` | process:Welding | pages::inspections.portable-spot.create |
| GET | `/inspections/robot-spot` | `inspections.robot-spot.index` | process:Welding | pages::inspections.robot-spot.index |
| GET | `/inspections/robot-spot/create` | `inspections.robot-spot.create` | process:Welding | pages::inspections.robot-spot.create |

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
| GET | `/work-stations/{work}/edit` | `work-stations.edit` | pages::work-stations.edit |

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
3. Opens **New Inspection** form — selects part, work station, stage.
4. Fills type-specific checklist (see below).
5. Submits — record is **final immediately** (no approval step).
6. **Index board** shows daily production matrix: parts × stages (S/M/E) × shifts, with color-coded status badges and clickable history modal.

### Per-Type Checklist Logic

| Type | Fields | Judgement |
|---|---|---|
| **Stamping** | Visual defect (Y/N), Jig/Spec OK (Y/N), Manual judgement (OK/NG/REPAIR) + remarks | Manual |
| **Station Spot** | Measurement value per hardware mapping (torque or nugget) | Auto (against `measurement_standards`) |
| **Portable Spot** | Pass/fail after hammer-and-chisel tap test + remarks | Manual |
| **Robot Spot** | Weld length, jig OK (Y/N) + remarks | Auto (against `weld_length_standards`) |

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
- 23 migrations covering all tables
- 14 Eloquent models with casts & relationships
- 6 enums with helper methods
- All routes with auth/process/admin middleware
- **All 4 inspection types** — both create forms and daily index boards
- Admin CRUD: Users, Parts, Hardware Types, Work Stations
- Part edit page: hardware mapping CRUD + weld length standard management
- Dashboard homepage with role-aware cards and today's summary
- Login page (NIK + password) + logout
- ShiftResolver utility
- Master data seeder + manager seeder

### Not Yet Built
- Meaningful tests (2 skeleton tests only; 6/8 factories empty)
- Git repository
- Reports/analytics (sidebar has placeholder link)
- Notifications (email/WhatsApp for NG results)
- Event/listener infrastructure
- Cache/queue job setup
- `process_id` field on user create/edit forms
- API routes
- Image upload storage config
