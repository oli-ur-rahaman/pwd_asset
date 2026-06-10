# Auto Template Autogen Roadmap

## Goal
Upgrade the segment-wise superadmin auto-generated Excel template into a protected, validated workbook that:
- keeps only the main data sheet visible
- hides helper sheets
- applies Excel-native validations and dropdowns
- supports dependent dropdowns
- marks required columns clearly
- checks missing required fields in the instruction column
- remains compatible with the current bulk import flow after superadmin edits and re-uploads it

## Scope Rules
- applies only to the auto-generated template download
- uploaded custom template flow remains unchanged
- file fields stay outside Excel import scope
- backend import audit remains the final authority

## Workbook Design
- visible sheet:
  - `{segment_name}_autogen`
- hidden sheet:
  - helper dropdown sheet with segment-based safe name
- row count:
  - 1000 entry rows
- password for protected cells and sheets:
  - `1234`

## Data Sheet Layout
- first column:
  - `Serial No`
  - locked
  - auto-filled 1 to 1000
- middle columns:
  - category if segment requires category selection
  - sub-category if segment requires sub-category selection
  - active import-enabled fields for the segment
- last column:
  - `Instruction`
  - locked
  - formula-driven

## Header Rules
- locked, wrapped, taller row height
- line-broken helper text inside headers
- non-editable columns:
  - append `view only, cannot edit` in red
- dropdown columns:
  - append `select from dropdown`
- date columns:
  - append `yyyy-mm-dd`
- mandatory at input:
  - append `required` in green
- mandatory at final submission:
  - append `required` in gray

## Validation Rules

### Dropdown and yes-no
- data validation list
- ignore blank on
- in-cell dropdown on
- short error alert

### Conditional dropdowns
- helper sheet named ranges
- primary dropdown from named range
- secondary dropdown from dependent `INDIRECT(...)` formula
- applies to:
  - category -> sub-category
  - superadmin-defined conditional fields

### Date
- cells formatted as text
- custom validation formula for exact `yyyy-mm-dd`
- short error alert

### Number
- no rule:
  - allow any numeric value
- rule present:
  - use custom validation formula generated from the system rule syntax
- preserve entered structure for exact-digit rules

### Text
- default:
  - no validation
- if max length is configured:
  - custom validation `LEN(cell)<=N`

## Instruction Column
- blank if the row is fully blank
- otherwise checks both:
  - mandatory at input
  - mandatory at final submission
- shows missing-field message if anything is missing
- shows `OK` if all required values are present

## Protection Rules
- lock all cells by default
- unlock only actual data-entry cells
- keep helper sheets hidden and protected
- keep header, serial number, and instruction column locked
- protection is structural, not security

## Compatibility Requirements
- main data sheet must remain the active sheet
- current import column order must be preserved
- first and last columns must still be serial and instruction
- parser should continue stripping first and last columns by position
- uploaded edited template must remain usable by the current import flow

## Implementation Phases
1. Replace simple auto-template export with a dedicated PhpSpreadsheet workbook builder.
2. Build visible data sheet and hidden helper sheet with segment-safe names.
3. Add formatting, sizing, borders, hidden gridlines, and 1000 prepared rows.
4. Add lock/unlock protection and password `1234`.
5. Add helper-sheet lists and named ranges for dropdown and conditional data.
6. Add validation rules for dropdown, conditional, date, number, and text-length fields.
7. Add rich-text headers with required and guidance markers.
8. Add instruction formulas for all 1000 rows.
9. Verify download and re-upload compatibility with the existing import workflow.

## Test Checklist
- category column omitted when segment does not need category selection
- sub-category column omitted when segment does not use sub-category
- dropdown validation works
- dependent dropdowns work
- date validation enforces `yyyy-mm-dd`
- number validation works for unrestricted and rule-based fields
- text length validation works when configured
- locked cells cannot be edited
- helper sheets are hidden
- instruction column updates correctly
- superadmin can edit and re-upload the workbook
