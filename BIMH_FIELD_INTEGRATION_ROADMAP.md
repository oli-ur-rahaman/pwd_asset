# BIMH Field Integration Roadmap

## Objective

Introduce a new field type named `BIMH` into `PWD Asset Database` so that users can enter a BIMH identifier, fetch approved establishment information from the BIMH platform, store the result locally, and use the stored values in tables, exports, audit, and later UI loads.

## Agreed Business Rules

### 1. User-editable vs read-only behavior

- user edits only the `BIMH ID`
- fetched BIMH attributes are read-only
- fetched attributes appear immediately after the BIMH field

### 2. Manual Add/Edit flow

- BIMH field has a small fetch icon beside it
- clicking the icon calls the BIMH API
- returned attributes populate read-only fields
- save stores both BIMH ID and fetched attributes

### 3. Bulk Upload / Audit flow

- Excel import contains only the BIMH ID
- attribute columns are not included as editable import columns
- after the normal audit modal opens, the system automatically fetches BIMH attributes row by row
- each audit row still has a fetch button for retry/correction
- only the BIMH field is editable in the audit row
- fetched attribute columns remain read-only

### 4. Runtime display behavior

- later UI loads use database values only
- no routine table rendering should depend on live BIMH API calls

### 5. Error presentation

- invalid BIMH ID: show a clear invalid status in attribute area
- connection or upstream issue: show clear connection/error status
- behavior should follow professional, understandable status handling

## Design Principles

1. Do not bind ordinary browsing to live BIMH API availability.
2. Keep BIMH fetch explicit in manual add/edit.
3. Keep bulk audit automatic after modal open, but still retryable per row.
4. Store fetched values locally for consistency and performance.
5. Treat fetched BIMH attributes as system-managed read-only fields.

## Architecture Proposal

## Phase 1. BIMH Settings Model

Goal: store BIMH connection and attribute configuration centrally.

### Planned DB additions

Create storage for BIMH integration settings, for example:

- base URL
- token endpoint path
- establishment lookup endpoint path
- auth header basic hash value
- username
- password
- ministry code
- active/inactive integration status
- request timeout

Recommended implementation:

- dedicated BIMH config storage table or structured info-row extension
- credentials should remain server-side only

### UI placement

Add a `BIMH Settings` card in superadmin `Profile`.

Contents:

- connection settings
- auth settings
- ministry code
- timeout / operational settings
- supported BIMH attribute catalog for internal mapping

Verification:

- superadmin can save BIMH settings
- settings are not exposed to normal users

## Phase 2. Add New Field Type `bimh`

Goal: extend field management to support a BIMH field type.

### Management behavior

When superadmin selects field type `bimh`:

- field create/edit UI should show BIMH-specific configuration
- BIMH-specific configuration should allow selecting which BIMH attributes will be pulled

### Companion field model

A BIMH field should create:

- one editable parent field for BIMH ID
- one read-only linked companion field per selected BIMH attribute

Companion fields must:

- be created together
- be updated together
- be enabled/disabled together
- be deleted together

Verification:

- BIMH field appears in management
- selected companion fields are linked correctly

## Phase 3. API Client Layer

Goal: implement server-side BIMH API communication.

### Required components

- token retrieval helper
- token caching / refresh strategy
- BIMH establishment lookup helper
- response normalizer
- structured error handling

### Required behavior

- bearer token request via OAuth 2.0 password grant
- establishment lookup using provider-confirmed BIMH ID parameter
- clean differentiation between:
  - invalid BIMH ID
  - auth failure
  - connection error
  - upstream service error

Verification:

- token generation succeeds with test credentials
- lookup succeeds for sample valid BIMH IDs
- error states are distinguishable

## Phase 4. Manual Add/Edit Modal Integration

Goal: support BIMH fetch during normal asset entry.

### UI behavior

- BIMH field renders as editable input
- fetch icon/button sits beside it
- selected attributes render immediately after it as read-only fields

### Interaction behavior

- clicking fetch calls server-side BIMH lookup endpoint
- returned values fill read-only BIMH companion fields
- save persists BIMH ID plus fetched attributes

Verification:

- add asset with BIMH fetch works
- edit asset with changed BIMH ID re-fetches and replaces read-only values

## Phase 5. Bulk Import and Audit Integration

Goal: support BIMH in import without overloading the Excel template.

### Template/import rules

- import template includes only BIMH ID
- BIMH companion attribute columns are excluded from import input

### Audit modal behavior

- after audit modal opens, system automatically fetches BIMH data row by row
- BIMH attribute columns appear as read-only
- each row has a fetch icon beside the BIMH input
- user can edit BIMH ID and re-fetch

### Validation behavior

- invalid BIMH ID remains visibly invalid
- connection error remains visibly distinct
- final save stores successful BIMH values

Verification:

- import with valid BIMH IDs auto-populates read-only columns
- corrected BIMH ID can be re-fetched in modal

## Phase 6. Local Storage and Display

Goal: ensure future UI rendering uses stored BIMH values.

### Storage rule

Store in DB:

- BIMH ID
- selected BIMH attribute values
- optional fetch status / timestamp if needed for auditability

### Runtime rule

Normal board/table/history/export rendering uses DB values only.

Verification:

- page reload does not trigger live BIMH re-fetch
- saved values remain visible in UI and exports

## Phase 7. Export and Template Behavior

Goal: keep import and export behavior clean.

### Import template

- BIMH ID only

### Export

- export includes BIMH ID
- export includes stored BIMH attribute columns

Verification:

- template excludes BIMH attribute inputs
- export includes stored attributes

## Phase 8. Security and Operational Hardening

Goal: make the integration production-safe.

### Security controls

- do not expose credentials in client-side code
- keep provider secrets server-side only
- use HTTPS only
- sanitize error output
- avoid logging bearer tokens or raw secrets

### Operational controls

- configurable timeout
- controlled retry behavior
- clear user-safe error messages
- optional sandbox/test mode support

Verification:

- no secrets leaked into HTML/JS
- logs contain only safe troubleshooting data

## Phase 9. Documentation and Handover

Goal: complete external and internal documentation.

Deliverables:

- external integration document for BIMH provider
- internal implementation notes
- field mapping registry
- operator troubleshooting notes

## Required Provider Inputs Before Coding Final API Layer

1. exact base URL
2. exact token endpoint URL if different from current note
3. exact BIMH lookup endpoint URL
4. exact BIMH ID request parameter name
5. test credentials or sandbox access
6. definitive response field dictionary
7. rate-limit and retry policy
8. sample valid and invalid BIMH IDs

## Proposed Data Mapping Strategy

Suggested initial attribute candidates:

- Structure Name
- Year of Construction
- Latitude
- Longitude
- Structure Type
- Foundation Type
- Concerned Ministry
- Administrative Division
- District

Final active list should be driven by:

- provider stability
- business need
- UI readability
- export/reporting usefulness

## Recommended Implementation Sequence

1. BIMH settings DB + superadmin profile card
2. new field type `bimh`
3. linked companion field model
4. BIMH token + lookup API client
5. manual add/edit fetch flow
6. audit modal auto-fetch + retry flow
7. storage/display/export integration
8. production hardening and final testing

## Manual Test Pack

### Field management

- create BIMH field
- select attributes
- confirm linked read-only companion fields are created

### Manual add/edit

- valid BIMH ID fetch
- invalid BIMH ID fetch
- connection failure handling

### Bulk audit

- upload Excel with BIMH IDs
- confirm audit modal auto-fetches row by row
- retry one row manually with fetch icon

### Persistence

- save asset
- reload board
- confirm BIMH attributes load from DB

### Export

- confirm BIMH ID and fetched attributes appear in export

## Current Status

Planning complete.

Implementation should begin after the provider confirms:

- final API contract
- exact request shape
- exact field dictionary
- environment access details
