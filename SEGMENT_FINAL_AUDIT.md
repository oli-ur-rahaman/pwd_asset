# Segment Final Audit

## Status

Segment support is now implemented through the main backend and UI flows:

- schema and `General` backfill
- active segment resolution
- segment selector on asset board
- segment-scoped management for categories, sub-categories, fields, and templates
- segment-aware asset listing
- segment-aware add/edit
- segment-aware bulk import and audit
- segment-aware template generation and uploaded-template usage
- segment-aware filters
- segment-aware downloads and exports
- segment-aware declarations
- segment-aware column visibility preferences and history linkage

## Final Audit Fixes

The final audit phase fixed these remaining leaks:

1. Board redirects after asset actions were still dropping back to plain `page=board`.
   - Fixed by centralizing a board redirect that preserves:
     - `segment_id`
     - `office_view_scope`

2. Admin redirects for some management actions were still returning to plain `page=admin`.
   - Fixed by preserving `segment_id` on those redirects.

3. Conditional field filter visibility was still resolving the parent field without an explicit segment.
   - Fixed by making `asset_filter_visible_fields()` accept and use `segment_id`.

4. Earlier segment pass regression fixes already included:
   - wrong field map segment during asset filtering
   - wrong field map segment during file sync
   - declarations `Last Update` not using selected segment

## DB Schema Audit

Audit result from local DB:

- `asset_categories` rows with null/zero `segment_id`: `0`
- `asset_subcategories` rows with null/zero `segment_id`: `0`
- `asset_fields` rows with null/zero `segment_id`: `0`
- `assets` rows with null/zero `segment_id`: `0`
- `office_asset_declarations` rows with null/zero `segment_id`: `0`
- `asset_import_batches` rows with null/zero `segment_id`: `0`
- `asset_activity_logs` rows with null/zero `segment_id`: `0`
- `asset_table_column_preferences` rows with null/zero `segment_id`: `0`

Current local segments:

- `General` (`active_status = 1`, `asset_subcategory_enabled = 0`)
- `Civil Information` (`active_status = 1`, `asset_subcategory_enabled = 1`)

## Completed Roadmap Phases

- Phase 1. Schema Design and Migration Skeleton
- Phase 2. Default Segment Backfill
- Phase 3. Segment-Aware Helper Layer
- Phase 4. Segment Management in Superadmin UI
- Phase 5. Segment Selector on Asset Board
- Phase 6. Segment-Aware Asset Listing
- Phase 7. Segment-Aware Add/Edit Asset
- Phase 8. Segment-Aware Bulk Entry and Audit
- Phase 9. Segment-Aware Templates
- Phase 10. Segment-Aware Filters
- Phase 11. Segment-Aware Downloads and Exports
- Phase 12. Segment-Aware Declarations
- Phase 13. Segment-Aware History and Preferences
- Phase 15. Final Code Audit
- Phase 16. Final DB Schema Audit

## Still Pending Before Calling It Fully Closed

Phase 14 and Phase 17 remain manual-validation heavy:

- permission and role audit across all user types
- full regression pack in both:
  - single-segment mode
  - multi-segment mode

## Manual Regression Pack

### Segment switching

- switch between `General` and another segment
- confirm board, management, and declarations all follow the selected segment

### Management

- create category in one segment and confirm it does not appear in another
- create field in one segment and confirm it does not appear in another
- upload template in one segment and confirm users in another segment do not get it
- verify sub-category visibility differs correctly by segment

### Board

- add asset in one segment and confirm it lists only in that segment
- edit asset in one segment and confirm the edit modal does not open under another segment
- test conditional, number-format, file, and unique fields in more than one segment

### Bulk import

- import into one segment
- confirm audit uses only that segment's categories, sub-categories, and fields
- confirm save goes into that segment only

### Download/export

- download data in two segments
- confirm exported headers and rows differ when the segment field structures differ

### Declarations

- declare in one segment
- confirm `Last Sent` changes only for that segment
- reset declarations in one segment from superadmin and confirm other segments stay untouched

### Column visibility and history

- hide columns in one segment and confirm another segment keeps separate preferences
- open history for assets in different segments and confirm the history remains correct

### Roles

- superadmin
- superadmin additional view-only
- office head
- office full access
- office view only

Check:

- `My Office`
- `Office Under Me`
- add/edit/delete permissions
- import permissions
- declare permissions
- download permissions
- read-only behavior
