# Excel Template Automation Plan

## Summary
Upgrade the system-generated segment-wise Excel template into a strict guided-entry workbook. The first version will apply only to the auto-generated superadmin template. It will use Excel-native validation, formatting, helper sheets, and protection so users are pushed toward valid input before upload, while the current import audit remains the final backend check.

Chosen defaults:
- strict Excel-side enforcement
- auto-generated template only for v1
- validate core structured field types in Excel
- 500 ready-to-fill rows per generated template
- protect workbook structure while keeping entry cells editable

## Field-by-Field Plan

### 1. General dropdowns and yes-no
- Use Excel data-validation dropdown lists, not free text.
- Source values from hidden helper sheets.
- Each dropdown field gets its own named range.
- `yes-no` uses a fixed named range with `Yes` and `No`.
- User can select only allowed values.
- Backend audit still rechecks all values in case of pasted or tampered files.

### 2. Conditional dropdowns, category-subcategory, and superadmin-made conditional fields
- Use dependent dropdown logic through hidden helper ranges plus formulas.
- `Category -> Sub-category`
  - category dropdown from active segment categories
  - sub-category dropdown depends on the chosen category in the same row
- Superadmin-made conditional fields
  - primary dropdown from configured primary options
  - secondary dropdown depends on the selected primary in the same row
- Parent fields always show their full valid list when opened.
- Child fields shrink to the matching valid subset only.
- Backend audit remains the final authority.

### 3. Number
- Apply Excel numeric validation.
- If no number format rule is configured:
  - allow general numeric input
- If a number rule is configured, such as:
  - `8.2`
  - `-8.2`
  - `*8.2`
  - `*8.*2`
  - `-*8.*2`
- Enforce as much as Excel validation can reliably support.
- Where Excel cannot express the full rule exactly:
  - use Excel validation for broad control
  - use backend audit for exact rule enforcement
- Add input guidance for the expected format.

### 4. Text
- Keep editable as text in v1.
- No strict Excel validation by default.
- Apply wrapping, width control, and consistent input-cell styling.
- Mandatory text fields will be checked by the instruction column.
- Backend audit remains responsible for deeper validation.

### 5. Date
- Use true Excel date validation where possible.
- Fix display format as `yyyy-mm-dd`.
- Show clear input guidance telling users to enter date as `YYYY-MM-DD`.
- Backend audit will still normalize and verify.

## Workbook Structure

### 1. Main entry sheet
- Contains the current import columns in required order.
- Contains 500 ready-to-fill data rows.
- Uses:
  - locked header row
  - locked control columns
  - unlocked input cells
  - frozen top row
  - wrapped headers
  - column widths tuned for readability

### 2. Hidden helper sheet(s)
- Store:
  - category lists
  - sub-category lists
  - dropdown options
  - conditional mappings
  - named ranges for validation formulas
- Hidden and protected from normal users.

### 3. Optional visible guidance sheet
- Can contain short user instructions:
  - how to fill data
  - date format rule
  - dropdown guidance
  - upload reminders

## Explicit Plan for Sheet Mechanics

### 1. Instruction column on the far right
- Keep one `Instruction` column at the very right of the entry sheet.
- Use an Excel formula in each row to check missing mandatory fields.
- If any mandatory value is missing, show a readable message such as:
  - `Missing: PROJECT NAME, DESIGN DATE`
- If all mandatory fields are filled:
  - leave blank in v1
- This is a helper only; backend audit still checks everything again.

### 2. Automatic serial number
- Keep `Serial No` as the first column.
- Pre-fill all 500 rows automatically:
  - row 2 = `1`
  - row 3 = `2`
  - and so on
- Serial number cells will be locked and not editable.

### 3. Restrict access to heading, serial number, and instruction column
- Header row will be locked.
- `Serial No` column will be locked.
- `Instruction` column will be locked.
- Only actual data-entry cells will be unlocked.
- Hidden helper sheets will also be locked.

### 4. Sheet protection
- Protect the main sheet with unlocked entry cells only.
- Protect helper sheets fully.
- Allow normal office use:
  - select unlocked cells
  - choose dropdowns
  - type into editable cells
- Prevent structural tampering:
  - editing locked cells
  - editing headers
  - editing helper lists
- No VBA or macros in v1.
- Protection is for structure safety, not secrecy.

## Segment-Aware Rules
- Template must reflect the active segment exactly.
- Include only active import-enabled fields for that segment.
- Omit category column if that segment does not require category selection.
- Omit sub-category column if that segment has no active sub-category workflow.
- Use only that segment’s active dropdown and conditional configuration.

## Backend Compatibility Rules
- Import parser must stay compatible with the same logical column order and keys.
- Backend audit remains mandatory and authoritative.
- Excel validation is a pre-entry guard, not a replacement for audit.
- Uploaded custom templates remain supported in v1 but are not auto-enhanced by this phase.

## Test Plan

### Template generation
- Download auto-template for multiple segments.
- Confirm each workbook differs correctly by segment:
  - categories
  - sub-categories
  - active import-enabled fields

### Excel behavior
- Category dropdown works.
- Sub-category depends on selected category.
- Conditional secondary depends on selected primary.
- Dropdown and yes-no fields reject invalid values.
- Date cells guide users to the chosen format.
- Number cells reject obvious invalid values.
- Locked cells cannot be edited.
- Entry cells remain editable.

### Import compatibility
- Fill valid rows in the generated template and confirm import passes.
- Upload intentionally tampered or invalid files and confirm backend audit still catches issues.
- Confirm segment-specific rules still apply during import.

### Protection and usability
- Users can fill 500 rows without workbook corruption.
- Paste behavior into editable ranges remains acceptable.
- Hidden/helper sheets do not interfere with normal user work.

## Assumptions
- V1 improves only the auto-generated superadmin template.
- Uploaded custom templates remain supported but are not auto-upgraded in this phase.
- Date guidance standardizes on `YYYY-MM-DD`.
- Excel-side validation should be strict for core structured fields, but backend audit remains final.
- No macros, no VBA, and no live external workbook connections will be used.
