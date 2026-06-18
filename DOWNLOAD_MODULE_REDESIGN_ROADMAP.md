# Download Module Redesign Roadmap

## Status
- Current status: **Runtime refactor in active progress**
- Existing state: the revised 3-page runtime modal is now partially implemented and wired to backend parsing/export rules
- This roadmap now reflects the **new target structure**
- Last update: `2026-06-18` (token policy revision applied)

## Revised Core Idea
The download flow will be reorganized into **three pages inside one download experience**. The UI should feel like moving through separate pages inside the same modal, with a clear top bar or folder-style navigation:

1. `Level_1`
2. `Level_2`
3. `Level_3`

This same concept should also be reflected in the superadmin `Download Manager` page so the planning and runtime flow stay aligned.

## Revised Page Design

### Page `Level_1`
This page controls the **common fields across all segments** plus **Office**.

Purpose:
- user selects exactly one field, or `Office`, as the `Level_1` grouping field
- user chooses the items/values under that Level_1 field
- user defines which other common fields should appear in the report table
- user can define the sequence/order of those common fields
- user can define ascending/descending sorting for those common fields
- the field selected as `Level_1` must not appear again in the remaining sort/filter choices

Expected contents:
- one selector for `Level_1` field
- available options:
  - all common fields across active segments
  - `Office`
- one item-selection area for the chosen Level_1 field
- one sequence/ordering area for the remaining common fields
- one compact sort setup area for the remaining common fields

Notes:
- this page defines the cross-segment/common part of the report
- these common fields should not be shown again in `Level_2`

### Page `Level_2`
This page controls the **segment-specific fields** that appear in the report/table.

Purpose:
- user selects the segments to include
- user selects the fields inside those selected segments
- common fields are excluded here because they are managed in `Level_1`

Rules:
- for `PDF` and `Excel`, all eligible segment fields may appear
- for `ZIP`, only file fields should appear
- for `PDF`/`Excel`, file fields should render as summaries like:
  - `1 dwg, 4 pdf`

Expected contents:
- selected segment list
- per-segment field list
- common fields excluded from these lists

### Page `Level_3`
This page controls **filters only**.

Purpose:
- user selects the segment field values to use as filters
- no sorting UI is required here in the revised model

Rules:
- filter UI is segment-wise
- filter fields are shown as compact collapsible controls
- a 4-column compact layout is preferred
- all filter fields are selected/openable by default
- if a field is the chosen `Level_1`, it must not appear here

Expected contents:
- per-segment filter sections
- collapsible compact filter cards/buttons
- 4-column layout where possible

## Revised Output Rules

### PDF
- one new page per `Level_1` item
- inside that Level_1 page:
  - separate table per segment
- default first column: `SL`

### Excel
- separate sheet per segment
- default first column: `SL`
- second column: chosen `Level_1`

### ZIP
- file only
- only file fields are eligible

## Download Manager Alignment
The superadmin `Download Manager` page should now mirror the revised structure using a top navigation/tab/folder style:

1. `Level_1`
2. `Level_2`
3. `Level_3`

Its purpose is to keep planning/configuration understandable before or while the runtime modal is refactored.

### Download Manager page expectations

#### `Level_1` tab
- show common fields across active segments
- also show `Office`
- allow choosing candidate Level_1 field(s)
- show that future sequence and sort controls belong here

#### `Level_2` tab
- show segment-wise non-common fields
- exclude common fields from the displayed lists
- communicate that this page corresponds to segment/field appearance in report output

#### `Level_3` tab
- keep segment-wise filter/sort field management visible here for now
- add segment-wise token management
- current persistence of field disabling/enabling must keep working after refresh
- common fields must appear as token candidates by default

## Revised Task Mapping

### Task 1: roadmap alignment
Status: **Completed**
- rewrite roadmap to match the revised 3-page structure
- explicitly separate:
  - common-field controls
  - segment-field controls
  - filter controls

### Task 2: Download Manager UI redesign
Status: **Completed**
- convert page into top-tab / folder-like multi-page layout
- add:
  - `Level_1` tab
  - `Level_2` tab
  - `Level_3` tab
- preserve current working save actions while improving structure

### Task 3: Download Manager data presentation update
Status: **Completed**
- `Level_1`:
  - show common fields
  - include `Office`
- `Level_2`:
  - show segment-wise non-common fields
- `Level_3`:
  - keep current filter/sort configuration surface visible

### Task 4: runtime download modal refactor
Status: **Completed**
- refactor the actual download modal to match the same 3-page structure
- keep `Download` / `Cancel` as shared modal actions outside the page bodies
- move sort responsibility out of `Level_3`
- add runtime `Level_1` sections for:
  - file format
  - Level_1 field/value selection
  - common columns
  - common sorting
- update `Level_2` so it now shows **segment-specific fields only**
- keep `Level_3` filters-only in the runtime modal
- fix the runtime bug where the selected `Level_1` field could still reappear in `Level_2`

### Task 5: runtime export alignment
Status: **In Progress**
- ensure Excel follows:
  - one sheet per segment
  - first column `SL`
  - second column chosen `Level_1`
- ensure PDF follows:
  - one new page per Level_1 item
  - separate segment tables
- ensure ZIP follows:
  - file-only logic
- completed in this phase:
  - `Office` is now available as a runtime `Level_1` option
  - common columns selected in `Level_1` are now carried into export headers/rows
  - common sorting selected in `Level_1` now drives row ordering inside each segment output
  - `Level_2` no longer forces common fields to be selected as segment fields
  - PDF renderer updated toward the revised structure:
    - landscape output
    - one Level_1 group per page section
    - separate table per segment
    - wrapped cell content
    - repeating table header support
    - page numbering
  - ZIP builder updated toward the revised structure:
    - `Level_1 → selected common fields → segment → file field → file`
    - file names now use the superadmin-configurable naming template
    - zero-file ZIP runs now return a valid archive instead of a corrupted response
  - naming token system upgraded:
    - default hardcoded tokens reduced to:
      - `office_name`
      - `sub-division`
      - `division`
      - `circle`
      - `zone`
      - `segment`
      - `field_name`
      - `office_type`
      - `asset_number`
    - common labels across all active segments now become token candidates by default
    - superadmin-declared token fields are included through field-level token selection
    - `Download Manager` Level_3 now manages:
      - filter fields
      - sort fields
      - token fields
    - token placeholders now support hyphenated names like `{sub-division}`
- still to verify carefully:
  - actual Excel/PDF/ZIP output files against all revised scenarios
  - any remaining gaps between export naming/layout and the final revised spec

### Task 6: regression verification
Status: **In Progress**
- confirm `Download Manager` save behavior survives refresh
- confirm no existing board features break
- confirm the later runtime refactor does not break permissions or export structure
- completed in this phase:
  - syntax checks passed for `app/lib/asset.php` and `app/views/board.php`
  - browser check completed with `ee_syl@pwd.gov.bd`
  - verified runtime modal now shows:
    - `Level_1` common columns/sorting
    - `Level_2` segment-specific fields only
  - direct HTTP download verification completed:
    - PDF response confirmed as valid `%PDF`
    - ZIP response confirmed as valid `PK` archive
- still pending:
  - targeted export-file verification for Excel/PDF/ZIP
  - broader regression sweep after export verification

## What To Check Now

### Download Manager structure
- top tab/folder navigation appears clearly
- clicking each tab feels like moving to a separate page inside one surface
- active tab is visually obvious

### `Level_1` tab
- common fields are shown
- `Office` is shown
- page communicates future sequence/sort responsibility clearly

### `Level_2` tab
- fields are shown segment-wise
- common fields are excluded from segment field lists

### `Level_3` tab
- current filter/sort disabling still saves
- current token disabling still saves
- removed fields stay removed after refresh
- common labels appear as token candidates by default before the first save
- naming helper shows only:
  - default tokens
  - common-token labels
  - superadmin-declared token labels

## Next Implementation Focus
The next major coding task should now be:

1. finish **runtime export verification and edge-case hardening**
2. then close the remaining gaps in Excel/PDF/ZIP layout/naming behavior against the revised spec
