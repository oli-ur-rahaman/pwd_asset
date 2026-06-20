# Download Module Redesign Roadmap

## Status
- Current status: **Phases 1-4 completed**
- Last update: `2026-06-20`

## Current Problem
The download module is functionally rich, but the runtime path is still doing too much repeated work:

1. it rebuilds segment field maps repeatedly
2. it reloads and re-filters assets separately for Excel and dataset grouping
3. it resolves common-field metadata and hierarchy logic multiple times
4. the Level_1 table does not yet expose filter buttons directly, even though the common-field filter modal already exists

This roadmap replaces the earlier generic runtime-refactor note with a **performance-first phased implementation plan**.

## New UI Requirement Included In This Plan
The `Level_1` table in the runtime download modal must now include:

1. one extra column before `Sorting`
2. that column contains a `Filter` button
3. clicking it opens the same modal previously used from the `Level_1 Fields` section of `Level_3`
4. for conditional dropdown pairs:
   - primary row and secondary row both open the **same shared modal**
5. common-field filter modals must include:
   - `Check All`
   - `Uncheck All`
6. when a common filter modal opens for list / hierarchy / conditional choices:
   - all available items are checked by default

## Refactor Goals

### Goal 1: faster request preparation
Build one reusable **download runtime context** per request instead of repeatedly resolving:
- assets
- fields
- filter field maps
- label-to-field maps
- common field maps

### Goal 2: earlier pruning
Apply:
- common filters
- segment filters
- Level_1 selection

before expensive row building and output formatting.

### Goal 3: export reuse
Make Excel / PDF / ZIP all read from the same prepared runtime dataset or segment-level prepared lists where possible.

### Goal 4: safer UI-to-backend alignment
The runtime `Level_1` filter UI should map directly to the existing common-filter parser, not add a second filter system.

## Phases

### Phase 0: planning and roadmap alignment
Status: **Completed**

Deliverables:
- rewrite roadmap around performance refactor
- include the new `Level_1` filter-column requirement
- define execution order so changes land safely

### Phase 1: Level_1 filter-column integration
Status: **Completed**

Scope:
- add `Filter` column to the runtime `Level_1` table
- wire row buttons to the existing common filter modal
- map conditional child row to the same modal as its parent row
- add `Check All` / `Uncheck All` buttons in the common filter modal
- make common list/hierarchy/conditional filter options checked by default when rendered

Expected gain:
- user-facing usability improvement
- no major speed gain yet
- creates clean alignment between Level_1 setup and common filter logic

### Phase 2: request-context foundation
Status: **Completed**

Scope:
- add one request-scoped `download context`
- preload once:
  - selected segments
  - accessible assets per selected segment
  - segment field maps
  - segment filter-field maps
  - common label maps
  - reusable hierarchy metadata
- cache prepared structures by request signature inside runtime

Expected gain:
- removes repeated helper work
- lowers repeated DB fetches and repeated field-map rebuilds

### Phase 3: early filtering and grouped dataset reuse
Status: **Completed**

Scope:
- move common filter pruning and segment filter pruning earlier
- build one grouped dataset for PDF / ZIP
- build one filtered-per-segment list for Excel from the same context
- stop recomputing the same matched asset lists in separate export paths

Expected gain:
- biggest backend runtime improvement for medium and large downloads

### Phase 4: format-specific tightening
Status: **Completed**

Scope:
- Excel:
  - use prepared field maps instead of re-reading fields inside row writers
  - avoid unnecessary per-cell repeated logic
- PDF:
  - keep markup simple and compact
  - reuse prepared rows and headers
- ZIP:
  - build file path plan once
  - reuse token maps and folder-part calculations where possible

Expected gain:
- noticeable reduction in generation overhead after filtering is done

### Phase 5: optional heavy-download strategy
Status: **Completed**

Scope:
- background job / queued generation
- saved-result reuse
- very large export handling without holding one long browser request

Implemented:
- queued download-job table and filesystem storage
- detached worker runner for background generation
- job-status polling and completed-file endpoint
- cached-result reuse for same user + same request signature
- modal submit switched from direct long request to queued download flow

## Execution Order

1. Phase 1: Level_1 filter-column integration
2. Phase 2: request-context foundation
3. Phase 3: early filtering and grouped dataset reuse
4. Phase 4: format-specific tightening
5. mark phases complete and define next test scope

## Code Targets

### UI files
- `app/views/download_modal_fragment.php`
- `app/views/board.php`

### Backend runtime
- `app/lib/asset.php`

Likely focus areas:
- `asset_download_request_from_input()`
- `asset_download_dataset()`
- `asset_download_filtered_assets_for_segment()`
- `asset_download_export_excel()`
- `asset_download_export_pdf()`
- `asset_download_export_zip()`
- new request-context helper(s)

## What To Test After Phase 1

1. open `Download Data`
2. in `Level_1`, verify a new `Filter` column exists
3. click filter button for:
   - Office
   - normal dropdown/text-like common field
   - conditional primary field
   - conditional secondary field
4. verify primary and secondary conditional rows open the same modal
5. verify `Check All` and `Uncheck All` work
6. verify hierarchy parents update correctly after bulk checking/unchecking

## What To Test After Phase 2 and 3

1. Excel download still works
2. PDF download still works
3. ZIP download still works
4. same filters produce same results as before
5. large runs open faster and finish faster than before
6. login and board loading are not slowed by the download refactor

## Completion Log

- `2026-06-20`: roadmap rewritten around performance refactor and Level_1 filter integration
- `2026-06-20`: Phase 1 completed
  - added `Filter` column in runtime `Level_1` table
  - Level_1 rows now open the existing common-filter modal
  - conditional primary/secondary rows share the same modal
  - added `Check All` and `Uncheck All`
  - common modal list / hierarchy options now render checked by default
- `2026-06-20`: Phase 2 completed
  - added request-scoped download runtime context
  - preloads accessible assets, fields, and effective filter field maps once per request
- `2026-06-20`: Phase 3 completed
  - added grouped-per-segment filtered asset reuse
  - Excel and dataset grouping now reuse the same filtered segment pass
  - reduced repeated `get_asset_fields()` calls in header/row builders for Excel and PDF
- `2026-06-20`: Phase 4 completed
  - added base filename-token caching per asset and segment
  - ZIP export now prebuilds per-segment field-label maps instead of rescanning field metadata inside nested loops
  - reduced repeated common-token hydration during file export
- `2026-06-20`: Phase 5 completed
  - added async download job table and worker script
  - added status polling endpoint and completed-file endpoint
  - switched modal submit to queued job flow
  - added same-request cached-result reuse for completed downloads
