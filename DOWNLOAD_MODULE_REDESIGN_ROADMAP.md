# Hierarchical Download Module Redesign

## Summary
Redesign the download module into a **hierarchical 3-layer system**.

1. **Layer 1: Supreme Level**
   - superadmin decides which fields are available as level-1 choices
   - user chooses **one** level-1 field for the run
   - user then chooses **all / one / multiple** values of that level-1 field
   - this level controls:
     - PDF page separation
     - Excel sheet separation
     - ZIP top-level folder separation

2. **Layer 2: Segments**
   - segment becomes the fixed second level
   - user chooses **all / one / multiple** segments
   - for each selected segment, user separately chooses which fields/columns are considered in output
   - each segment stays separate; no merged cross-segment table

3. **Layer 3: Segment Filters + Sort**
   - each selected segment has its own filter block
   - filters decide which rows of that segment are included
   - sort decides row order inside that segment’s output table
   - sort does **not** apply to level 1 or segment ordering

The redesigned module supports:
- **Excel**
- **PDF**
- **ZIP of files**

## Key Changes

### 1. Level 1 model
Add a dedicated superadmin setting: **available as first level**.

Rules:
- level 1 options are chosen only from **common fields**
- common-field identity is **manual**, not inferred
- level 1 is a **single selected field** per download run
- level 1 is the **supreme grouping key**
- level 1 is **not reused** in:
  - segment table columns
  - sort chains
- if level-1 value is missing in data, it is treated as **Blank**

Level-1 output behavior:
- **PDF**: one new page group per selected level-1 item
- **Excel**: one sheet per selected level-1 item
- **ZIP**: one top-level folder per selected level-1 item

### 2. Level 2 segment model
After level 1 is chosen, user selects:
- all segments
- one segment
- multiple segments

For each selected segment:
- user gets a separate configuration block
- user chooses which fields/columns of that segment are included
- existing field serial/order defines column order
- no download-only column reorder in v1

Output behavior:
- each selected segment is rendered as a **separate table/section**
- segment is fixed as **level 2**
- segment ordering is outside sorting logic

Format behavior:
- **Excel**:
  - inside each level-1 sheet, render separate segment tables one after another
- **PDF**:
  - inside each level-1 page group, render separate segment tables one after another
- **ZIP**:
  - inside each level-1 folder, create separate segment folders

### 3. Layer 3 filters
Each selected segment has its own independent filter block.

Filter sources:
- default download filters, if superadmin enabled them:
  - office hierarchy
  - category
  - sub-category
- superadmin-declared segment filter fields:
  - dropdown
  - yes/no
  - conditional fields
  - other allowed fields

Rules:
- filter fields are configured **per segment**
- filter values are always **all / one / multiple**
- filter options are generated from data by:
  - grab
  - sort
  - duplicate remove
- blank values are included as **Blank** where present

Filter semantics:
- Layer 3 filters only decide **which rows** of that segment are considered
- filters do not affect:
  - level-1 grouping
  - segment ordering
  - selected output columns

### 4. Layer 3 sorting
Sorting is configured only from superadmin-enabled sort fields.

Rules:
- sort applies **inside each segment table only**
- level 1 and level 2 are outside sorting logic
- user can define **multi-level sort**
- each sort level has:
  - one field
  - asc / desc

Example:
- if sort is `infrastructure_condition > office > subcategory > category`
- rows inside each selected segment table are ordered by that chain only

### 5. Output format behavior

#### Excel
Structure:
- workbook
- one sheet per selected level-1 item
- inside each sheet:
  - separate table for each selected segment
  - each segment uses its own selected columns
  - each segment applies its own filters
  - each segment rows are sorted by the chosen sort chain

#### PDF
Structure:
- one PDF
- one page/section group per selected level-1 item
- inside each level-1 group:
  - separate table for each selected segment
  - each segment uses its own selected columns
  - each segment applies its own filters
  - each segment rows are sorted by the chosen sort chain

#### ZIP
Structure:
- one ZIP archive
- top level: one folder per selected level-1 item
- next level: one folder per selected segment
- inside each segment folder:
  - apply segment-specific filters
  - include only selected file fields of that segment
  - use ZIP hierarchy rules if needed below segment level
  - if extra ZIP hierarchy is added later, it sits **below segment**

### 6. Download settings model
Add a dedicated download configuration layer.

Superadmin settings must support:
- mark field as **common field**
- mark field as **available as first level**
- enable default filters globally
- enable download filters per segment
- enable download sort fields per segment
- enable ZIP-eligible file fields per segment
- maintain naming token settings globally

This configuration is separate from current board/table filter settings.

### 7. Naming model
Use token-builder naming, not free-text parsing.

Naming applies to:
- Excel filename
- PDF filename
- ZIP filename
- ZIP internal file names
- ZIP folder labels where appropriate

Minimum token set:
- level1_field_name
- level1_value
- segment_name
- office_name
- office_type
- category
- subcategory
- field_name
- district
- upazila_thana
- division
- circle
- zone
- asset_number
- serial_no
- bimh_id
- date_stamp

Rules:
- join non-empty tokens only
- sanitize for filesystem safety
- handle duplicate ZIP file names safely

## Implementation Task Mapping

### Phase 1: configuration foundation
- add download-specific field metadata:
  - `is_common_download_field`
  - `is_download_level1`
  - `is_download_filter`
  - `is_download_sort`
  - `is_download_zip_file_selectable`
- add global download settings storage for:
  - enabled default filters
  - naming token order
- add backend helpers to resolve:
  - common fields
  - level-1 eligible fields
  - per-segment download filters
  - per-segment sort fields
  - per-segment ZIP-selectable file fields
- update superadmin UI to maintain these settings

### Phase 2: request contract and domain model
- define one canonical download request payload containing:
  - output type
  - chosen level-1 field
  - chosen level-1 values
  - selected segments
  - selected fields per segment
  - selected filters per segment
  - selected sort chain per segment
  - selected ZIP file fields per segment
- build backend validation for the payload
- reject invalid combinations cleanly:
  - no level-1 field
  - no segment
  - no fields selected for a segment
  - ZIP selected with no file field chosen

### Phase 3: download modal redesign
- redesign the current modal into 3 blocks:
  - Layer 1 selector block
  - Layer 2 segment and field-selection block
  - Layer 3 per-segment filter and sort block
- preselect current segment when modal opens from a segment page
- allow segment selection to expand to all / one / multiple
- render one segment configuration block per selected segment
- keep current dependency logic for:
  - office hierarchy
  - category/sub-category
  - conditional fields
- make Layer 3 filters multi-select

### Phase 4: data extraction pipeline
- implement a hierarchical fetch pipeline:
  1. resolve selected level-1 values
  2. iterate each selected level-1 item
  3. iterate each selected segment
  4. fetch that segment’s assets
  5. apply per-segment filters
  6. keep only rows matching the current level-1 item
  7. sort inside the segment table
  8. project only selected fields for that segment
- ensure no segment merging happens in output
- ensure blank level-1 values map to `Blank`

### Phase 5: excel export
- create workbook builder for:
  - one sheet per selected level-1 item
  - separate segment tables inside each sheet
- preserve selected segment order
- preserve selected field order from existing field serial
- include segment/table headings clearly

### Phase 6: pdf export
- create report renderer for:
  - one PDF
  - one section/page group per selected level-1 item
  - separate segment tables inside each group
- use same filtered/sorted/projected dataset as Excel
- keep layout table-based, not card-based

### Phase 7: zip export
- create ZIP builder for:
  - one folder per selected level-1 item
  - one folder per selected segment under it
  - selected file fields only
- map matching rows/files into ZIP structure
- use naming token builder for archive file names and internal file names
- implement duplicate-safe file renaming

### Phase 8: naming/token builder
- implement token resolution service
- support all required tokens
- skip empty token values
- sanitize filesystem-invalid characters
- provide stable duplicate handling for repeated names

### Phase 9: regression and acceptance hardening
- verify current permissions still gate downloads correctly
- verify segment-aware access still holds
- verify board filters and normal asset workflows are untouched
- verify view-only users retain intended download access

## Test Plan

### Core scenarios
- one level-1 field, one level-1 value, one segment, Excel
- one level-1 field, multiple level-1 values, one segment, PDF
- one level-1 field, multiple level-1 values, multiple segments, Excel
- one level-1 field, multiple level-1 values, multiple segments, PDF
- one level-1 field, multiple level-1 values, multiple segments, ZIP
- different selected columns per segment in the same run
- different filters per segment in the same run
- multi-select filter values per segment
- multi-level sort inside each segment table

### Edge cases
- level-1 blank values produce `Blank` sheet/page/folder
- segment with no matching rows after filtering under a level-1 item
- selected segment with no selected file field in ZIP mode
- duplicate file names inside ZIP
- conditional filters with multi-select values
- category/sub-category linked behavior inside per-segment filter blocks

### Regression
- current permission boundaries remain unchanged
- current segment-aware access rules remain unchanged
- view-only users keep download access consistent with current behavior
- existing board filters and table behavior remain unchanged

## Assumptions and Defaults
- level 1 is chosen from manually marked common fields only
- level 1 is one field per run
- level 1 is supreme and is not shown again in table columns or sorting
- level-1 missing values are grouped as `Blank`
- segment is always fixed as level 2
- user may choose all / one / multiple segments
- each segment has separate selected columns
- each segment has separate filter controls
- sorting applies only inside each segment table
- multiple segments are never merged into one table in v1
