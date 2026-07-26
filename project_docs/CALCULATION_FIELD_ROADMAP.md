# Calculation Field Roadmap

Last updated: July 26, 2026
Status: Code implementation completed, runtime verification pending real calculation-field test data

## Progress Snapshot

- Phase 1: completed
- Phase 2: completed
- Phase 3: completed
- Phase 4: completed
- Phase 5: completed
- Phase 6: completed
- Phase 7: completed
- Phase 8: completed
- Phase 9: completed

## Summary

Introduce a new field type called `calculation` that works in:

- normal segments
- common-field segments
- fixed segments
- addable segments
- user-defined common segments
- superadmin-defined common segments

The field is non-editable by users in:

- add info
- edit modal
- bulk import audit modal
- Excel templates

Instead, the system computes the value automatically from a formula declared by superadmin, using same-row values from the same segment.

The calculated result will be:

- shown in UI tables
- shown in add/edit/audit screens as read-only
- persisted in `asset_values`
- regenerated in Excel templates as locked formula cells
- recalculated whenever dependent row data changes

## Locked Product Decisions

### Field definition model

- new field type name: `calculation`
- superadmin declares:
  - label
  - field key
  - result type
  - formula
  - existing common flags / display / import / filter / download options as applicable

### Result type

Superadmin explicitly selects one result type:

- `number`
- `date`
- `text`

This result type controls:

- where the value is stored in `asset_values`
- how it is displayed
- how it is exported
- how it behaves in template formula formatting

### Recalculation scope

The system recalculates:

- on add info save
- on edit save
- on bulk import save
- on user-defined common propagation
- on superadmin-defined common row save
- on superadmin-defined common row Excel import save
- on formula change
- on result type change
- on calculation field creation if rows already exist

Formula or result-type change must backfill all existing rows of that segment.

### Bulk import behavior

- Excel upload does not trust user-entered value in calculation columns
- server recomputes calculation result from editable source fields
- audit modal shows calculation field as read-only computed value

### Supported formula outputs

- `error` is the user-facing fallback text when calculation fails
- `inword(...)` is English-only in v1

### Dependency scope

- calculation fields can reference other calculation fields in the same segment
- chained calculation is allowed
- circular dependency is not allowed
- cross-segment reference is not allowed
- cross-row reference is not allowed

## Formula Language

### Field reference syntax

- field reference format: `{field_key}`
- reference always means:
  - same segment
  - same row

### Allowed base source field types

Direct references are allowed for:

- `number`
- `date`
- `calculation`

Direct references are not allowed for:

- `text`
- `dropdown`
- `yes_no`
- `file`
- `conditional`
- `bimh`

### Supported operators

- `+`
- `-`
- `*`
- `/`
- `%`

### Supported functions in v1

- `sum(...)`
- `average(...)`
- `max(...)`
- `min(...)`
- `count(...)`
- `roundup(value,digits)`
- `rounddown(value,digits)`
- `if(condition, value_if_true, value_if_false)`
- `iferror(value, fallback)`
- `sqrt(value)`
- `year(date_value)`
- `month(date_value)`
- `day(date_value)`
- `date(year, month, day)`
- `inword(number_value)`

### Example formulas

Number result:

- `={capacity}+{floor_area}`
- `=sum({capacity},{number_of_units},{reserve})`
- `=average({room_1},{room_2},{room_3})`
- `=roundup(({built_area}/{land_area})*100,2)`
- `=if({total_seats}=0,0,({occupied_seats}/{total_seats})*100)`

Text result:

- `=if({completion_percent}=100,"Completed","In Progress")`
- `=inword({approved_amount})`

Date result:

- `=date(year({start_date}),12,31)`
- `=date(year({handover_date})+1,month({handover_date}),day({handover_date}))`

Chained example:

- `gross_area = {length}*{width}`
- `service_charge = {gross_area}*1.5`
- `total_charge = {service_charge}+{fixed_charge}`

## Implementation Phases

## Phase 1: Schema and metadata foundation

### Tasks

- extend `asset_supported_data_types()` to include `calculation`
- add schema columns to `asset_fields`:
  - `calculation_formula`
  - `calculation_result_type`
- ensure schema upgrade path works in `ensure_asset_schema()`
- define allowed result type helper
- decide and implement default values for non-calculation fields

### Measurable outcomes

- calculation appears in field type dropdown source
- schema bootstraps without SQL error on fresh DB
- schema upgrade adds both new columns on existing DB
- non-calculation fields continue reading normally with null calculation metadata

### Acceptance checks

- creating non-calculation fields still works unchanged
- existing fields list loads without warnings
- schema re-run is idempotent

## Phase 2: Admin field definition UI and validation

### Tasks

- add `calculation` option to create/edit form in admin page
- show calculation-specific inputs only when `calculation` is selected:
  - result type
  - formula
- show an info block listing usable formula field references from current segment
- extend `validate_asset_field_definition()` for:
  - formula required
  - result type required
  - valid placeholders only
  - same-segment references only
  - allowed reference types only
  - no self-reference
- add dependency graph validation for chained calculation fields
- reject cycles with clear error message

### Measurable outcomes

- admin can see and submit calculation-specific settings
- invalid formula is blocked before save
- circular references are rejected
- valid chained formulas are accepted

### Acceptance checks

- create calculation field with valid number formula
- create calculation field with invalid placeholder and see rejection
- create 2 chained calculation fields successfully
- try circular dependency and confirm save is blocked

## Phase 3: Formula parser and evaluator

### Tasks

- create parser for formula syntax using `{field_key}` placeholders
- normalize optional leading `=`
- implement supported operators and functions
- build row-scoped evaluation context from normalized row values
- support dependency-ordered evaluation for chained calculations
- return typed normalized value according to declared result type
- standardize failure output to `error`

### Measurable outcomes

- same formula gives same result across normal UI, common rows, import audit, and export preparation
- number/date/text results are normalized into the correct storage slot
- failed evaluation produces stable `error`

### Acceptance checks

- evaluate arithmetic formula
- evaluate date function formula
- evaluate text-returning `if(...)`
- evaluate `iferror(...)`
- evaluate `inword(...)`
- verify invalid runtime input returns `error`

## Phase 4: Save pipeline integration

### Tasks

- exclude user editing authority over calculation fields during ordinary input normalization
- after editable field normalization, compute all calculation fields for the row
- merge computed normalized values into `save_asset_values()`
- add helper to recalculate one asset row by segment rules
- add helper to backfill all rows for one segment
- trigger backfill after:
  - calculation field creation
  - formula change
  - result type change

### Measurable outcomes

- calculation field values are persisted automatically on row save
- old rows are refreshed after formula update
- no manual input can overwrite stored calculation values

### Acceptance checks

- add new row and confirm calculation stored
- edit a source number and confirm calculation updates
- change formula and confirm all existing rows update
- change result type and confirm stored value column changes accordingly

## Phase 5: UI rendering integration

### Tasks

- add read-only rendering for calculation fields in add/edit modal
- show computed value in board tables
- reuse result-type-specific formatting:
  - number display helper for number result
  - date display for date result
  - text display for text result
- show `error` when computation failed
- ensure table sorting/filtering/downloading reads persisted result as ordinary field value

### Measurable outcomes

- calculation field is visible but not editable in forms
- board tables show computed result correctly
- existing table rendering is not broken for other field types

### Acceptance checks

- add/edit modal shows read-only calculation field
- board table shows updated result after save
- failed formula row shows `error`

## Phase 6: Excel template generation

### Tasks

- include calculation columns in all relevant generated templates:
  - normal segment template
  - user-side common templates
  - superadmin common templates
- translate admin formula into Excel formula using row-relative cell references
- lock calculation cells
- keep calculation header marked as non-editable
- apply result-type formatting:
  - number style
  - date style
  - text style
- preserve same UI column sequence rules already used by each template path

### Measurable outcomes

- template contains formula cells for calculation columns
- calculation cells are locked
- formula follows row sequence correctly

### Acceptance checks

- generated normal template shows formula in calculation column
- generated common template shows formula in calculation column
- user cannot edit locked calculation cell
- formula result changes in Excel when source cells change

## Phase 7: Bulk import and audit integration

### Tasks

- keep calculation columns visible in import audit, but read-only
- ignore uploaded calculation value as authoritative input
- recompute calculation result server-side from editable submitted fields
- make save path commit recomputed values for:
  - non-common segments
  - user-defined fixed
  - user-defined addable
  - superadmin-defined fixed
  - superadmin-defined addable
- ensure no extra editable controls appear for calculation columns in audit modal

### Measurable outcomes

- audit modal displays correct computed value
- save validated rows persists computed value
- tampering in uploaded calculation cells does not affect saved result

### Acceptance checks

- upload normal template with changed source values
- upload common fixed template
- upload common addable template
- confirm audit shows calculation read-only
- confirm save stores recomputed value only

## Phase 8: Common-field system integration

### Tasks

- allow calculation fields in common-supported field type logic
- user-defined common:
  - generated child rows compute after inherited values are available
  - addable child manual rows also compute
- superadmin-defined common:
  - fixed rows compute from row values
  - addable user rows compute too
- ensure locked/common row rules stay unchanged for source fields

### Measurable outcomes

- calculation field works uniformly in all 4 common scenarios
- generated common rows and manual addable rows both compute correctly

### Acceptance checks

- user-defined fixed test
- user-defined addable test
- superadmin-defined fixed test
- superadmin-defined addable test

## Phase 9: Regression hardening

### Tasks

- verify non-calculation field create/edit still works
- verify conditional fields remain unaffected
- verify import/export/template generation for old field types remains unchanged
- verify common-row locking logic remains intact
- verify no slowdown or fatal errors are introduced in board rendering

### Measurable outcomes

- existing field types behave exactly as before
- no common-field flow is broken
- no unexpected save/import failure appears

### Acceptance checks

- create/edit text, number, dropdown, conditional, bimh fields
- run normal bulk import on non-calculation segment
- run common bulk import on existing tested common segment
- open board, edit, save, export templates without error

## Test Matrix

## A. Field definition tests

1. Create `calculation + number` field with `={capacity}*{rate}`
Expected:
- field saves
- appears in field list as `calculation`
- result type is stored as `number`

2. Create `calculation + text` field with `=if({status_count}>0,"OK","Pending")`
Expected:
- field saves
- field is read-only in forms

3. Create `calculation + date` field with `=date(year({start_date}),12,31)`
Expected:
- field saves
- date result type stored

4. Use bad placeholder like `{unknown_field}`
Expected:
- save blocked with validation message

5. Create circular pair
Expected:
- save blocked with cycle error

## B. Normal segment tests

1. Add row with source values
Expected:
- calculation column auto-fills
- user cannot edit it manually

2. Edit source value
Expected:
- calculation changes after save

3. Force bad runtime scenario
Expected:
- UI shows `error`

## C. Excel template tests

1. Download normal template
Expected:
- calculation column present
- locked
- formula visible in formula bar

2. Change source cells in Excel
Expected:
- calculation cell result changes in workbook

3. Upload workbook
Expected:
- server recomputes and saves
- uploaded manual change in calculation cell is ignored

## D. Common segment tests

### D.1 User-defined fixed

Expected:
- inherited rows compute
- no manual row add allowed
- calculation field locked

### D.2 User-defined addable

Expected:
- inherited rows compute
- manual added rows compute too

### D.3 Superadmin-defined fixed

Expected:
- predefined rows compute
- fixed row restrictions remain intact

### D.4 Superadmin-defined addable

Expected:
- predefined rows compute
- additional user rows compute too

## E. Formula backfill tests

1. Create calculation field after data already exists
Expected:
- old rows get calculated values

2. Change formula
Expected:
- old rows get recalculated

3. Change result type
Expected:
- old rows get recalculated and stored in new typed slot

## F. Regression tests

1. Create old field types
Expected:
- unchanged behavior

2. Download old segment templates
Expected:
- unchanged except for segments where calculation fields exist

3. Bulk import old segment without calculation fields
Expected:
- unchanged behavior

4. Common-field tested segments from previous module
Expected:
- all still working

## Implementation Order Recommendation

Implement strictly in this order:

1. Phase 1
2. Phase 2
3. Phase 3
4. Phase 4
5. Phase 5
6. Phase 6
7. Phase 7
8. Phase 8
9. Phase 9

Do not start template/import/common integration before parser, validation, and save pipeline are stable.

## Progress Tracker

| Phase | Title | Status | Notes |
|---|---|---|---|
| 1 | Schema and metadata foundation | Completed | Supported type, schema columns, and result-type helpers are in place. |
| 2 | Admin field definition UI and validation | Completed | Create/edit UI, reference validation, and cycle rejection are wired. |
| 3 | Formula parser and evaluator | Completed | Server-side evaluator, dependency ordering, and typed normalization are wired. |
| 4 | Save pipeline integration | Completed | Calculation values recompute on save paths and segment-wide backfill hooks are added. |
| 5 | UI rendering integration | Completed | Add/edit, common-field forms, and audit screens render calculation fields read-only. |
| 6 | Excel template generation | Completed | Normal/common templates output locked formula cells for calculation columns. |
| 7 | Bulk import and audit integration | Completed | Import staging ignores manual calc edits and recomputes values server-side; add-row JS now keeps calc cells read-only. |
| 8 | Common-field system integration | Completed | User-defined and superadmin-defined common-row flows compute calculation values in all supported scenarios. |
| 9 | Regression hardening | Completed | PHP syntax checks passed and login/board smoke check passed; real runtime formula verification still needs actual calc-field test data. |

## Notes

- This roadmap assumes existing row-sequence logic in normal/common templates must be preserved.
- Calculation field reads only same-row values.
- Chained calculations are allowed only inside one segment.
- `error` is the standard visible fallback in v1.
- `inword(...)` is English-only in v1.
