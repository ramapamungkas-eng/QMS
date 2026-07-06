# Managing Parts

A **part** in the QMS represents a single physical component that flows through one or more work station types (Stamping → Station Spot → Portable Spot → Robot Spot). Each part has a unique part number and can be associated with multiple station types.

---

## Access

Navigate to **Master Data → Parts** (requires Manager or Leader/Admin role).

---

## Creating a Part

Click **New part** and fill in:

| Field | Required | Description |
|---|---|---|
| **Part number** | Yes | Unique identifier. Cannot be duplicated. |
| **Part name** | Yes | Display name. |
| **Model** | No | Model name for identification. |
| **Variant** | No | Variant name for identification. |
| **Photo** | No | JPEG or PNG, up to 2MB. |
| **Station Types** | Yes (at least 1) | Which station types this part is inspected at. |

### Station Types

Each part must be assigned to at least one station type. The station types determine:
- Which inspection forms are available for this part
- Which sections appear on the edit page (e.g., hardware mappings for Station Spot, weld length standards for Robot Spot)

A part can span multiple station types — for example, a stamped body panel may also be inspected at Station Spot welding.

After saving, you're redirected to the **edit page** to configure hardware mappings and standards.

---

## Editing a Part

The edit page is organized into sections:

### Identity Section

Update basic information and change which station types the part belongs to. Toggling a station type on/off affects what you can configure below.

### Hardware Mappings (Station Spot)

Shown only when **Station Spot** is selected as a station type.

A **hardware mapping** links a hardware type (nut/bolt) to the part with a specific measurement type and tolerance standard. Each mapping represents a physical piece of hardware installed on the part.

| Field | Description |
|---|---|
| **Hardware** | Select from registered hardware types (e.g. M6 Nut). |
| **Measurement type** | `Torque` or `Nugget` — determines the measurement method and default unit. |
| **Usage qty** | How many of this hardware are physically installed on the part. The checker still enters one representative measurement value. |
| **Min value / Max value** | Acceptable range for auto-judgement. |
| **Unit** | Display unit (defaults based on measurement type, but can be overridden). |

Click **Add hardware** to create a new mapping. A modal form appears for data entry.

**Note:** The pair `(hardware_type + measurement_type)` must be unique per part — you cannot add the same hardware with the same measurement type twice.

### Weld Length Standards (Robot Spot)

Shown only when **Robot Spot** is selected as a station type.

A **weld length standard** defines the acceptable weld length range for a specific work station. Since standards can differ per work station, each Robot Spot station can have its own min/max values.

| Field | Description |
|---|---|
| **Work Station** | Select a Robot Spot work station (e.g. "RS-1"). |
| **Min length / Max length** | Acceptable weld length range. |
| **Unit** | Display unit (defaults to mm). |

Click **Add standard** to create a new one. The combination `(part + work_station)` must be unique.

**Note:** If a part at a Robot Spot work station has no weld length standard configured, the inspection form **hides** the Weld Length Measurement section entirely — the checker only sees the visual/jig check.

---

## Part Lifecycle Across Station Types

A single part can be inspected at multiple station types. Here's a typical flow:

```
Part created
    │
    ▼
Stamping (body panel formed)
    │
    ▼
Station Spot (hardware installed & measured)
    │
    ▼
Portable Spot (tap test pass/fail)
    │
    ▼
Robot Spot (weld length measured)
```

Each station type's inspection is recorded independently. The same part row is used throughout — no duplicate part rows per process.

---

## Deleting a Part

- Deleting a part removes all associated hardware mappings, measurement standards, and weld length standards.
- Inspection records referencing the part are **not** deleted (they remain for audit purposes).

---

## Seeded Sample Parts

| Part Number | Name | Station Types | Robot Spot? | Weld Standard |
|---|---|---|---|---|
| PR-1001 | Body Panel Front | Stamping, Station Spot, Portable Spot, Robot Spot | Yes | 10–15 mm |
| PR-1002 | Body Panel Rear | Stamping, Station Spot, Portable Spot, Robot Spot | Yes | 10–15 mm |
| PR-1003 | Door Inner LH | Stamping, Station Spot, Portable Spot, Robot Spot | Yes | 8–12 mm |
| PR-1004 | Door Inner RH | Stamping, Station Spot, Portable Spot, Robot Spot | Yes | 8–12 mm |
| PR-2001 | Cross Member Assembly | Station Spot | No | — |
| PR-2002 | Bracket Support | Robot Spot | Yes | 5–10 mm |
