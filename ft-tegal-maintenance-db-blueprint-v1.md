# FT Tegal Maintenance Management System — DB Blueprint v1

## 1. Project Direction

The system will be rebuilt specifically for **FT Tegal**.

Architecture:

```text
                    ┌──────────────────┐
                    │   Laravel API    │
                    │   + Database     │
                    └────────┬─────────┘
                             │
                 ┌───────────┴───────────┐
                 │                       │
                 ▼                       ▼
        ┌────────────────┐      ┌─────────────────┐
        │ Filament Admin │      │ Android Worker  │
        │     Panel      │      │      App       │
        └────────────────┘      └─────────────────┘
```

- **Laravel** = backend, API, business logic, authentication, database access.
- **Filament** = admin panel for master data, planning, monitoring, approval, reporting.
- **Android app** = field-worker application for technicians/workers.
- Android must communicate through Laravel API.
- Business logic should remain in Laravel, not inside Filament.

---

## 2. Scope Decisions

### Included in core DB

- Locations / Areas
- Equipment Types
- Equipment
- Activities
- Users
- Roles
- Maintenance Requests
- Maintenance Plans
- Maintenance Schedules
- Work Orders
- Work Order Activities
- Work Order Workers
- Meter Logs

### Explicitly skipped for now

- Companies
- Vendors
- Spareparts
- Grouping
- Scoping
- Equipment Documents

Maintenance Record / History will be designed later as part of the custom FT Tegal system.

---

## 3. Location Concept

The original system used Company → Plant → Section → Equipment.

For FT Tegal, **Company is removed** because the application focuses on one company/location.

The location structure should be flexible enough for areas such as:

```text
FT Tegal
├── Unloading RTW
│   ├── Pump
│   └── Other Equipment
├── Storage Tank
│   ├── Tank
│   └── Other Equipment
├── MCC
│   └── Panel / Electrical Equipment
├── Loading MT
│   ├── Pump
│   └── Other Equipment
├── Unloading MT
│   ├── Pump
│   └── Other Equipment
├── Air Compressor
├── Filling / Pengisian
├── Genset
└── Fire Fighting
    ├── Fire Pump
    └── Foam System
```

Recommended implementation is a self-referencing `locations` table:

```text
locations
    └── parent_id → locations.id
```

This allows arbitrary location hierarchy.

---

# 4. Database Tables

## 4.1 locations

| Field | Type | Key | Notes |
|---|---|---|---|
| id | BIGINT UNSIGNED | PK | |
| parent_id | BIGINT UNSIGNED NULL | FK | Parent location |
| code | VARCHAR(50) | UNIQUE | Location code |
| name | VARCHAR(100) | | Location name |
| description | TEXT NULL | | |
| latitude | DECIMAL(10,7) NULL | | Optional map coordinate |
| longitude | DECIMAL(10,7) NULL | | Optional map coordinate |
| status | BOOLEAN | | Active/inactive |
| created_at | TIMESTAMP | | |
| updated_at | TIMESTAMP | | |

Relationship:

```text
locations
    └── hasMany child locations
```

---

## 4.2 equipment_types

Examples:

- Pump
- Storage Tank
- Compressor
- Genset
- Panel
- Fire Pump

| Field | Type | Key | Notes |
|---|---|---|---|
| id | BIGINT UNSIGNED | PK | |
| code | VARCHAR(50) | UNIQUE | |
| name | VARCHAR(100) | | |
| description | TEXT NULL | | |
| status | BOOLEAN | | |
| created_at | TIMESTAMP | | |
| updated_at | TIMESTAMP | | |

Relationship:

```text
equipment_types
    └── hasMany equipment
```

---

## 4.3 equipment

Equipment is the central entity of the system.

| Field | Type | Key | Notes |
|---|---|---|---|
| id | BIGINT UNSIGNED | PK | |
| location_id | BIGINT UNSIGNED | FK | Current location |
| equipment_type_id | BIGINT UNSIGNED | FK | Equipment type |
| tag_number | VARCHAR(50) | UNIQUE | Example: P-001 |
| name | VARCHAR(150) | | |
| classification | VARCHAR(50) | | Mechanical/Electrical/Instrument |
| floor_level | VARCHAR(50) NULL | | |
| latitude | DECIMAL(10,7) NULL | | Equipment coordinate |
| longitude | DECIMAL(10,7) NULL | | Equipment coordinate |
| criticality_level | TINYINT UNSIGNED NULL | | 1–5 |
| risk_level | TINYINT UNSIGNED NULL | | 1–25 |
| serial_number | VARCHAR(100) NULL | | |
| model_number | VARCHAR(100) NULL | | |
| size_capacity | VARCHAR(100) NULL | | |
| maker | VARCHAR(100) NULL | | |
| installation_date | DATE NULL | | |
| operation_unit | VARCHAR(30) NULL | | hour/day/etc |
| information | TEXT NULL | | Other information |
| status | VARCHAR(30) | | Active/inactive |
| health_status | VARCHAR(30) NULL | | Good/Bad/etc |
| operation_status | VARCHAR(30) NULL | | Running/Stop/etc |
| image | VARCHAR(255) NULL | | Equipment image |
| created_at | TIMESTAMP | | |
| updated_at | TIMESTAMP | | |

Relationships:

```text
location
    └── hasMany equipment

equipment_type
    └── hasMany equipment

equipment
    ├── belongsTo location
    └── belongsTo equipment_type
```

---

## 4.4 users

Workers are represented as users because the same identity will be used by the admin panel and Android app.

| Field | Type | Key | Notes |
|---|---|---|---|
| id | BIGINT UNSIGNED | PK | |
| name | VARCHAR(150) | | |
| email | VARCHAR(150) | UNIQUE | |
| password | VARCHAR(255) | | Hashed |
| employee_number | VARCHAR(50) NULL | UNIQUE | |
| phone | VARCHAR(30) NULL | | |
| status | BOOLEAN | | |
| created_at | TIMESTAMP | | |
| updated_at | TIMESTAMP | | |

### User Roles & Permissions

User roles and permissions will be managed by **Filament Shield**.

Do NOT create custom `roles` or `role_user` tables for this project.

Filament Shield will handle role/permission management. Application-level foreign keys such as:

```text
created_by
issued_by
reported_by
technician_coordinator_id
recorded_by
user_id
```

will still reference `users.id`.

---

---

## 4.6 activities

Master maintenance activities.

Examples:

- Cleaning
- Function Test
- Replace Bearing
- Oil Inspection

| Field | Type | Key | Notes |
|---|---|---|---|
| id | BIGINT UNSIGNED | PK | |
| equipment_type_id | BIGINT UNSIGNED | FK | Activity is associated with equipment type |
| name | VARCHAR(150) | | |
| type | VARCHAR(50) | | Cleaning, Function Test, etc. |
| maintenance_classification | VARCHAR(30) | | Preventive/Corrective/etc |
| interval | VARCHAR(30) NULL | | Daily/Monthly/Yearly/etc |
| answer_type | VARCHAR(30) | | Qualitative/Quantitative |
| reference | TEXT NULL | | |
| optimum | DECIMAL(12,3) NULL | | |
| minimum | DECIMAL(12,3) NULL | | |
| maximum | DECIMAL(12,3) NULL | | |
| unit | VARCHAR(30) NULL | | |
| status | BOOLEAN | | |
| created_at | TIMESTAMP | | |
| updated_at | TIMESTAMP | | |

Relationship:

```text
equipment_type
    └── hasMany activities
```

---

# 5. Maintenance Flow

## 5.1 Maintenance Request

Used for unscheduled/corrective maintenance reports.

| Field | Type | Key | Notes |
|---|---|---|---|
| id | BIGINT UNSIGNED | PK | |
| equipment_id | BIGINT UNSIGNED | FK | Reported equipment |
| request_number | VARCHAR(50) | UNIQUE | |
| reported_by | BIGINT UNSIGNED | FK | User |
| operation_status | VARCHAR(30) | | |
| description | TEXT | | Trouble/request description |
| damage_date | DATE | | |
| damage_time | TIME NULL | | |
| equipment_condition | TEXT NULL | | |
| impact | TEXT NULL | | |
| early_action | TEXT NULL | | |
| status | VARCHAR(30) | | |
| created_at | TIMESTAMP | | |
| updated_at | TIMESTAMP | | |

Relationship:

```text
equipment
    └── hasMany maintenance_requests
```

---

## 5.2 maintenance_plans

Represents the maintenance plan/template.

| Field | Type | Key | Notes |
|---|---|---|---|
| id | BIGINT UNSIGNED | PK | |
| equipment_id | BIGINT UNSIGNED | FK | |
| maintenance_classification | VARCHAR(30) | | |
| interval | VARCHAR(30) | | Daily/Monthly/Yearly/etc |
| start_date | DATE | | |
| end_date | DATE NULL | | |
| created_by | BIGINT UNSIGNED | FK | User |
| technician_coordinator_id | BIGINT UNSIGNED NULL | FK | User |
| description | TEXT NULL | | |
| status | VARCHAR(30) | | |
| created_at | TIMESTAMP | | |
| updated_at | TIMESTAMP | | |

---

## 5.3 maintenance_plan_activities

Links a plan to its activities.

| Field | Type | Key |
|---|---|---|
| id | BIGINT UNSIGNED | PK |
| maintenance_plan_id | BIGINT UNSIGNED | FK |
| activity_id | BIGINT UNSIGNED | FK |
| sort_order | SMALLINT UNSIGNED | |
| created_at | TIMESTAMP | |

Relationship:

```text
maintenance_plan
    └── hasMany plan_activities

plan_activity
    └── belongsTo activity
```

---

## 5.4 maintenance_schedules

Represents actual scheduled occurrences shown in Planner.

| Field | Type | Key | Notes |
|---|---|---|---|
| id | BIGINT UNSIGNED | PK |
| maintenance_plan_id | BIGINT UNSIGNED | FK | |
| equipment_id | BIGINT UNSIGNED | FK | |
| scheduled_date | DATE | | |
| status | VARCHAR(30) | | Pending/Rescheduled/Done/etc |
| description | TEXT NULL | | |
| rescheduled_from | DATE NULL | | |
| created_at | TIMESTAMP | | |
| updated_at | TIMESTAMP | | |

Concept:

```text
Maintenance Plan
    ↓
Maintenance Schedule
    ↓
Planner calendar
```

---

# 6. Work Order

## 6.1 work_orders

Work Order is the main maintenance transaction.

A WO can originate from:

```text
Preventive:
Maintenance Plan → Schedule → Work Order

Corrective:
Maintenance Request → Work Order
```

| Field | Type | Key | Notes |
|---|---|---|---|
| id | BIGINT UNSIGNED | PK | |
| work_order_number | VARCHAR(50) | UNIQUE | |
| equipment_id | BIGINT UNSIGNED | FK | |
| maintenance_plan_id | BIGINT UNSIGNED NULL | FK | Preventive source |
| maintenance_schedule_id | BIGINT UNSIGNED NULL | FK | Schedule source |
| maintenance_request_id | BIGINT UNSIGNED NULL | FK | Corrective source |
| issued_by | BIGINT UNSIGNED | FK | User |
| technician_coordinator_id | BIGINT UNSIGNED NULL | FK | User |
| classification | VARCHAR(30) | | Preventive/Corrective/etc |
| interval | VARCHAR(30) NULL | | |
| start_at | DATETIME | | |
| finish_at | DATETIME NULL | | |
| note | TEXT NULL | | |
| status | VARCHAR(30) | | |
| created_at | TIMESTAMP | | |
| updated_at | TIMESTAMP | | |

---

## 6.2 work_order_activities

Stores execution/result of an activity.

Important: this is NOT the activity master. It is the activity execution record for a specific WO.

| Field | Type | Key | Notes |
|---|---|---|---|
| id | BIGINT UNSIGNED | PK | |
| work_order_id | BIGINT UNSIGNED | FK | |
| activity_id | BIGINT UNSIGNED | FK | Master activity |
| reference | TEXT NULL | | |
| pre_inspection | VARCHAR(100) NULL | | |
| follow_up | VARCHAR(100) NULL | | |
| final_result | VARCHAR(100) NULL | | |
| unit | VARCHAR(30) NULL | | |
| executed | BOOLEAN | | |
| note | TEXT NULL | | |
| created_at | TIMESTAMP | | |
| updated_at | TIMESTAMP | | |

Flow:

```text
Activity Master
      ↓
WO Activity
      ├── Pre Inspection
      ├── Follow Up
      └── Final Result
```

---

## 6.3 work_order_workers

A WO can be assigned to multiple workers.

| Field | Type | Key |
|---|---|---|
| id | BIGINT UNSIGNED | PK |
| work_order_id | BIGINT UNSIGNED | FK |
| user_id | BIGINT UNSIGNED | FK |
| role | VARCHAR(50) NULL | |
| started_at | DATETIME NULL | |
| finished_at | DATETIME NULL | |
| note | TEXT NULL | |

Relationship:

```text
work_order
    └── hasMany work_order_workers
            └── belongsTo user

User roles/permissions are handled by Filament Shield.

---

# 7. Meter

## 7.1 meter_logs

Historical meter readings for each equipment.

| Field | Type | Key | Notes |
|---|---|---|---|
| id | BIGINT UNSIGNED | PK | |
| equipment_id | BIGINT UNSIGNED | FK | |
| reading_date | DATE | | |
| value | DECIMAL(15,3) | | |
| recorded_by | BIGINT UNSIGNED | FK | User |
| created_at | TIMESTAMP | | |
| updated_at | TIMESTAMP | | |

Example:

```text
Equipment: GRD-05411
Operation Unit: hours

2026-09-16 → 1200
2026-09-17 → 1210
2026-09-18 → 1220
```

Meter values may later be used to update/calculate maintenance planning.

---

# 8. Main Relationship Diagram

```text
                         ┌─────────────────┐
                         │    locations    │
                         │   parent_id     │
                         └────────┬────────┘
                                  │
                                  ▼
                         ┌─────────────────┐
                         │    equipment    │
                         └───────┬─────────┘
                                 │
              ┌──────────────────┼────────────────────┐
              │                  │                    │
              ▼                  ▼                    ▼
        meter_logs       maintenance_requests   maintenance_plans
                                                       │
                                                       ▼
                                              plan_activities
                                                       │
                                                       ▼
                                                   activities
                                                       │
                                                       │
                                                       ▼
                                            maintenance_schedules
                                                       │
                                                       ▼
                                                  work_orders
                                                   /                                                         /                                                          ▼           ▼
                                    work_order_activities  work_order_workers
                                             │                    │
                                             ▼                    ▼
                                         activities             users
                                                                  │
                                                                  ▼
                                                        Filament Shield
                                                        (roles/permissions)
```

Equipment type relationship:

```text
equipment_types
       │
       ├──< equipment
       │
       └──< activities
```

---

# 9. Core Business Flow

### Preventive Maintenance

```text
Equipment
    ↓
Maintenance Plan
    ↓
Plan Activities
    ↓
Maintenance Schedule
    ↓
Work Order
    ↓
Assign Workers
    ↓
Pre Inspection
    ↓
Maintenance / Activity Execution
    ↓
Follow Up
    ↓
Final Result
    ↓
Finish
```

### Corrective Maintenance

```text
Equipment
    ↓
Maintenance Request
    ↓
Approval / Scheduling
    ↓
Work Order
    ↓
Assign Workers
    ↓
Maintenance
    ↓
Final Result
    ↓
Finish
```

---

# 10. Android App Direction

Field workers will use Android.

Minimum Android capabilities planned:

```text
Login
    ↓
My Work Orders
    ↓
Work Order Detail
    ↓
Activities
    ├── Pre Inspection
    ├── Follow Up
    └── Final Result
    ↓
Photo / Documentation
    ↓
Update Progress
    ↓
Finish Work Order
```

Laravel API examples:

```text
POST /api/login

GET  /api/work-orders
GET  /api/work-orders/{id}

POST /api/work-orders/{id}/activities
POST /api/work-orders/{id}/photos
POST /api/work-orders/{id}/finish
```

These endpoints are examples only and should be finalized during API design.

---

# 11. Important Design Principles

1. Laravel is the source of truth.
2. Filament is an admin interface, not the business-logic layer.
3. Android consumes Laravel API.
4. Equipment is the central entity.
5. Locations use a self-referencing hierarchy.
6. Activity master and executed WO activity must remain separate.
7. Maintenance Plan and Maintenance Schedule are separate concepts.
8. Maintenance Request is primarily for unscheduled/corrective maintenance.
9. Sparepart/inventory is intentionally excluded from v1.
10. Company/Vendor/Grouping/Scoping/Document modules are intentionally excluded from v1.
11. Status/classification/interval values should remain flexible until the final workflow is confirmed.
12. Do not create Laravel migrations until the schema and workflow are reviewed and finalized.

---

# 12. Current Status

This document represents the **initial DB blueprint (v1)** based on reverse-engineering the existing maintenance system UI and adapting it to the FT Tegal use case.

It is NOT a final schema.

Before implementation, review:

- Location hierarchy
- Equipment fields
- Activity structure
- Maintenance Plan vs Schedule
- Work Order lifecycle
- User/role model
- Android workflow
- Maintenance History
- Photos/documentation
- Notifications
- Reliability/availability
- Approval workflow
