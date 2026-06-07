# Segment Implementation Roadmap

## Objective
Turn the current single-segment asset system into a multi-segment system where each segment has its own:

- categories
- sub-categories
- asset fields
- filters
- Excel template
- bulk upload structure
- declaration state per office

Global features that stay outside segment scope:

- Office Orders
- user accounts and user-management framework
- interface settings unless explicitly changed later

## Agreed Product Rules

### Segment access
- All users can access all segments.
- Each segment will have its own `+Add Asset` and `Bulk Entry` flow.

### Template behavior
- Each segment has:
  - one auto-generated template available only in superadmin UI
  - one manually uploaded user-facing template
- Users download only the uploaded template of the currently selected segment.

### Segment selector UI
- Segment selector will appear below the office identity card.
- It will render one button per segment.
- If there is only one segment and it is the default `General`, do not show the segment selector card.

### Default segment
- If superadmin creates no segment, system will create/use a default segment named `General`.
- All current existing data must be migrated into `General`.

### Declaration behavior
- Declarations become per office per segment.
- Superadmin declaration monitoring page must also become segment-aware.

### Office Orders
- Office Orders stay global.

### Segment-aware existing features
These must become segment-aware:

- asset add/edit
- bulk import and audit modal
- export/download
- table visibility preferences
- filters
- history/activity
- declaration tracking

## Design Principles

1. Do not attempt one huge patch.
2. Land the work in controlled phases.
3. Keep the app runnable after each phase where possible.
4. Backfill and audit DB data before enabling dependent UI.
5. Prefer additive schema changes first, then behavioral rewrites.

## Phase Plan

### Phase 0. Baseline Freeze and Audit
Goal: freeze current working state before segment changes.

Tasks:
- Back up live DB.
- Back up live code.
- Capture current schema snapshot.
- Capture current asset-related routes and core helper entry points.
- Confirm current single-segment behavior still works before touching schema.

Files likely involved:
- none required for code change

Verification:
- app loads
- add/edit asset works
- import/export works
- declarations work

Exit criteria:
- baseline snapshot and rollback plan exist

---

### Phase 1. Schema Design and Migration Skeleton
Goal: introduce segment schema without breaking current code.

New table:
- `segments`

Suggested columns:
- `id`
- `segment_name`
- `active_status`
- `sort_order`
- `created_at`
- `updated_at`

Add `segment_id` to:
- `assets`
- `categories`
- `subcategories`
- `asset_fields`
- declaration storage
- uploaded template storage/settings
- `asset_table_column_preferences`
- any asset-history/activity tables

Potential additional tables/settings to review:
- template metadata storage in `info`
- any records currently treated as globally asset-scoped

Tasks:
- add schema migrations in `ensure_asset_schema()`
- update fresh install schema in `app/sql/schema.sql`
- create helper to get/create default `General` segment

Files likely involved:
- `app/lib/asset.php`
- `app/sql/schema.sql`

Verification:
- schema sync completes cleanly
- no existing rows lost
- `General` segment created if none exists

Exit criteria:
- all required segment columns/tables exist

---

### Phase 2. Default Segment Backfill
Goal: migrate existing records into `General`.

Tasks:
- create or fetch `General`
- backfill `segment_id` in:
  - assets
  - categories
  - subcategories
  - asset_fields
  - declarations
  - template metadata
  - column visibility preferences
  - asset activity/history
- make `segment_id` required where safe after backfill

Special rule:
- if only `General` exists, the UI must behave like current single-segment mode and hide segment picker

Files likely involved:
- `app/lib/asset.php`

Verification queries:
- count of rows with null `segment_id` should be zero for migrated tables
- every current asset record points to `General`

Exit criteria:
- all existing data belongs to a valid segment

---

### Phase 3. Segment-Aware Helper Layer
Goal: refactor backend helpers to accept `segment_id`.

Tasks:
- add active-segment resolver helper
- add segment list loader
- update category loaders by segment
- update sub-category loaders by segment
- update field loaders by segment
- update field validation helpers by segment
- update template lookup by segment
- update declaration lookup/write by segment
- update table visibility preference helpers by segment
- update history/activity helpers by segment

Key principle:
- no asset structure lookup should remain globally scoped

Files likely involved:
- `app/lib/asset.php`
- `app/lib/data.php` if info/template storage requires it

Verification:
- helper unit smoke tests via targeted manual checks
- existing default `General` segment behaves like old system

Exit criteria:
- helpers can return correct asset structure for a passed segment

---

### Phase 4. Segment Management in Superadmin UI
Goal: let superadmin manage segments safely.

Tasks:
- add segment management card/page in superadmin UI
- create segment
- rename segment
- enable/disable segment
- block delete if segment contains dependent data

Nice-to-have later:
- sort order control

Files likely involved:
- `app/views/admin.php` or a separate segment view/page
- `index.php`
- `app/lib/asset.php`

Verification:
- create second segment
- rename it
- disable it
- ensure disabled segments are not user-selectable

Exit criteria:
- superadmin can manage segments safely

---

### Phase 5. Segment Selector on Asset Board
Goal: add segment buttons below the office info card.

Tasks:
- render segment selector below office/user identity card
- hide segment selector if only one segment exists
- store current segment in board request state
- keep `My Office` and `Office Under Me` tabs, but scope both to active segment

Behavior:
- changing segment reloads the page in that segment context
- all following board content depends on selected segment

Files likely involved:
- `app/views/board.php`
- `index.php`
- `app/lib/asset.php`
- `public/assets/app.js` if client state is needed

Verification:
- switching segment changes visible categories/fields
- single-segment mode hides segment card

Exit criteria:
- active segment controls the entire board scope

---

### Phase 6. Segment-Aware Asset Listing
Goal: list only assets of the selected segment.

Tasks:
- update all board asset queries to require active `segment_id`
- update `My Office` listing by segment
- update `Office Under Me` listing by segment
- preserve sorting, column visibility, history, provider column
- keep `Office Under Me` read-only

Files likely involved:
- `app/lib/asset.php`
- `app/views/board.php`

Verification:
- assets from one segment do not appear in another
- subordinate-office data is filtered to the same segment

Exit criteria:
- no cross-segment asset mixing in board tables

---

### Phase 7. Segment-Aware Add/Edit Asset
Goal: create/edit assets within selected segment only.

Tasks:
- add `segment_id` to asset create flow
- add `segment_id` to asset edit flow
- use segment-specific:
  - categories
  - sub-categories
  - fields
  - conditional mappings
  - unique checks
  - number format rules
  - file field rules

Files likely involved:
- `app/views/board.php`
- `index.php`
- `app/lib/asset.php`
- `public/assets/app.js`

Verification:
- asset created in segment A does not appear in segment B
- edit respects only current segment field schema

Exit criteria:
- manual entry fully segment-aware

---

### Phase 8. Segment-Aware Bulk Entry and Audit
Goal: make import fully segment-specific.

Tasks:
- template parsing uses active segment
- audit modal validates against active segment only
- category/sub-category resolution is segment-aware
- field validation is segment-aware
- final save writes `segment_id`
- file fields remain out of scope for bulk import

Files likely involved:
- `app/lib/asset.php`
- `app/views/board.php`
- `public/assets/app.js`
- `index.php`

Verification:
- upload valid file for segment A succeeds in segment A
- same file structure should fail in segment B if schema differs
- audit modal uses segment-specific categories/fields

Exit criteria:
- bulk import behaves correctly per segment

---

### Phase 9. Segment-Aware Templates
Goal: separate templates per segment.

Tasks:
- store uploaded custom template per segment
- generate auto-template per segment
- superadmin can download auto-template for active segment
- superadmin can upload custom template for active segment
- users download only custom uploaded template for active segment
- info-sheet sync must create/update categories/sub-categories inside the same segment only

Files likely involved:
- `app/lib/asset.php`
- `app/views/admin.php`
- `asset_template.php`
- `index.php`

Verification:
- upload different custom templates for two segments
- users get the correct template per selected segment
- info-sheet sync does not leak categories into other segments

Exit criteria:
- template lifecycle is completely separated by segment

---

### Phase 10. Segment-Aware Filters
Goal: rebuild board filters to operate only inside active segment.

Tasks:
- category/sub-category filters by segment
- dynamic field filters by segment
- conditional filters by segment
- file extension filters by segment
- date filters by segment
- office hierarchy filters still work, but only within current segment data
- keep typing-dropdown UI

Files likely involved:
- `app/lib/asset.php`
- `app/views/board.php`
- `public/assets/app.js`
- `public/assets/style.css`

Verification:
- filter choices in segment A differ from segment B as expected
- office hierarchy filters still backfill dependent upper/lower filters

Exit criteria:
- no filter option is sourced from another segment

---

### Phase 11. Segment-Aware Downloads and Exports
Goal: export current segment only.

Tasks:
- office user `Download Data` exports active segment only
- `Office Under Me` export exports subordinate-office data for active segment only
- superadmin export becomes segment-aware
- exported columns come from current segment schema only

Files likely involved:
- `app/lib/asset.php`
- `index.php`
- `app/views/board.php`

Verification:
- export from segment A excludes segment B assets
- per-segment columns match the active segment fields

Exit criteria:
- downloads are correctly segment scoped

---

### Phase 12. Segment-Aware Declarations
Goal: make declaration state per office per segment.

Tasks:
- update declaration write path to include `segment_id`
- update office-side `Last Sent` and `Last Update` calculations by segment
- update declaration reset logic by segment
- update superadmin declarations page to monitor per office per segment

Files likely involved:
- `app/lib/asset.php`
- `app/views/declarations.php`
- `app/views/board.php`
- `index.php`

Verification:
- declaring segment A does not mark segment B complete
- declarations page distinguishes office + segment state correctly

Exit criteria:
- declaration model is no longer global per office

---

### Phase 13. Segment-Aware History and Preferences
Goal: finish secondary systems.

Tasks:
- make column visibility preferences include `segment_id`
- make history/activity display implicitly segment-aware through asset linkage
- review any remaining asset-scoped settings for missing segment keys

Files likely involved:
- `app/lib/asset.php`
- `app/views/board.php`

Verification:
- same user can configure different column visibility in different segments
- history remains correct for assets in each segment

Exit criteria:
- secondary asset features are segment-safe

---

### Phase 14. Permission and Role Audit
Goal: make sure all roles behave properly in segment context.

Roles to verify:
- superadmin
- superadmin additional view-only user
- office head
- full access office user
- view only office user

Tasks:
- confirm board actions remain role-correct in each segment
- confirm `Office Under Me` stays read-only across segments
- confirm view-only users still cannot modify assets/import/declare
- confirm file access routes respect segment-scoped asset ownership

Files likely involved:
- `app/lib/auth.php`
- `app/lib/asset.php`
- `index.php`
- relevant views

Verification:
- manual role-by-role checks

Exit criteria:
- no permission regression caused by segment introduction

---

### Phase 15. Final Code Audit
Goal: systematic review for missed global assumptions.

Audit checklist:
- asset queries
- category queries
- sub-category queries
- field loaders
- unique checks
- conditional field rules
- number format validation
- file field rules
- import staging
- import final save
- template generation
- template upload sync
- export routes
- declaration routes
- history routes
- visibility preference routes
- provider column logic

Expected output:
- list of any remaining non-segment-scoped code paths

Exit criteria:
- no unresolved global asset-structure assumptions remain

---

### Phase 16. Final DB Schema Audit
Goal: confirm data integrity after migration and code changes.

Audit queries to run:
- rows with null `segment_id`
- rows with invalid `segment_id`
- duplicate segment names
- orphaned categories/sub-categories/fields by segment
- declarations without valid segment
- template metadata without valid segment
- preferences without valid segment where required

Exit criteria:
- schema is internally consistent

---

### Phase 17. Regression Test Pack
Goal: prove the system still works end-to-end.

Test in both:
- single-segment mode
- multi-segment mode

Test roles:
- superadmin
- superadmin additional view-only
- office head
- full access
- view only

Test office levels:
- zone
- circle
- division
- sub-division

Test flows:
- segment switching
- add asset
- edit asset
- file upload
- bulk import
- audit modal
- export/download
- declaration
- declaration monitoring
- Office Under Me
- template upload/download
- filters
- history
- column visibility

Exit criteria:
- no regressions in current functionality
- segment separation works correctly

## Recommended Work Order
Implement in this exact order:

1. Phase 1: schema design
2. Phase 2: default-segment backfill
3. Phase 3: segment-aware helper layer
4. Phase 5: segment selector on board
5. Phase 6: segment-aware listing
6. Phase 7: add/edit asset
7. Phase 8: bulk entry
8. Phase 9: templates
9. Phase 10: filters
10. Phase 11: exports
11. Phase 12: declarations
12. Phase 13: history/preferences
13. Phase 14: permission audit
14. Phase 15: final code audit
15. Phase 16: final DB schema audit
16. Phase 17: regression pack

## Suggested Delivery Strategy
Do not attempt to finish all phases in one coding run.

Recommended milestone batches:

### Milestone A
- Phase 1
- Phase 2
- Phase 3

### Milestone B
- Phase 4
- Phase 5
- Phase 6
- Phase 7

### Milestone C
- Phase 8
- Phase 9
- Phase 10

### Milestone D
- Phase 11
- Phase 12
- Phase 13

### Milestone E
- Phase 14
- Phase 15
- Phase 16
- Phase 17

## Final Audit Deliverable
When the work is complete, prepare a final audit report with these sections:

1. Schema changes completed
2. Data migration completed
3. Code areas made segment-aware
4. Remaining known limitations
5. Role/permission verification
6. Single-segment mode verification
7. Multi-segment mode verification
8. Rollback considerations

## Notes for Future Implementation
- Prefer using a single active segment resolver rather than passing raw segment state inconsistently through views.
- Avoid leaving any asset-structure query globally scoped.
- Keep Office Orders and user-management global unless explicitly redesigned later.
- If segment delete is ever implemented, require strict dependency checks first.
