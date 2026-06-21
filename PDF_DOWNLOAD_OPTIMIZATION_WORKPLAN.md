# PDF Download Optimization Workplan

## Goal
Reduce full-database PDF download time without breaking the currently working Excel, ZIP, queue, or filter behavior.

Current observed behavior:
- Full DB Excel: about 30-40 seconds
- Full DB PDF: about 4-5 minutes

This strongly suggests the main bottleneck is PDF rendering, not the general data download pipeline.

## Backups Taken
Before any PDF optimization work, the current working files were backed up here:
- [asset.php.bak](D:\Laragon-root\www\pwd_asset\backup\pdf_optimization_2026-06-20\asset.php.bak)
- [exports.php.bak](D:\Laragon-root\www\pwd_asset\backup\pdf_optimization_2026-06-20\exports.php.bak)
- [board.php.bak](D:\Laragon-root\www\pwd_asset\backup\pdf_optimization_2026-06-20\board.php.bak)

## Working Assumption
The queue and worker flow are now functional enough for filtered and full Excel exports. The remaining heavy cost for PDF is expected to come from one or more of these:
- very large HTML generation
- repeated row/value preparation before render
- Dompdf layout/render time for wide multi-page tables
- memory-heavy string building for the final PDF body

## Phase 1: Profiling
Status: pending

Tasks:
- Add timing checkpoints around the PDF pipeline only.
- Measure these stages separately:
  - request parsing
  - dataset build
  - grouped dataset reuse
  - row preparation
  - HTML generation
  - Dompdf render
  - final file write
- Keep the logging lightweight and removable.
- Ensure Excel and ZIP code paths are not slowed down by the profiling.

Success criteria:
- We can identify which stage is the primary cost on a full DB PDF export.

## Phase 2: Low-Risk Data Path Tightening
Status: pending

Tasks:
- Reuse already prepared grouped data more strictly for PDF.
- Avoid duplicate row/value formatting work where possible.
- Prepare only the values needed by PDF output.
- Remove unnecessary intermediate arrays if they are only used once.
- Keep behavior identical for:
  - headers
  - row ordering
  - level 1 grouping
  - segment separation

Success criteria:
- PDF uses less repeated backend work before the renderer starts.

## Phase 3: PDF HTML/CSS Simplification
Status: pending

Tasks:
- Simplify table markup specifically for PDF mode.
- Reduce expensive CSS that Dompdf handles slowly.
- Keep:
  - landscape orientation
  - repeated table headings
  - wrapped text
  - page numbering
- Reduce:
  - unnecessary wrappers
  - extra borders/background complexity
  - avoidable inline markup noise
- Keep layout readable and professional.

Success criteria:
- Dompdf receives simpler HTML and renders faster without changing report meaning.

## Phase 4: Large Report Compact Mode
Status: pending

Tasks:
- Add an automatic compact PDF mode for very large exports.
- Trigger it only when row or cell volume crosses a threshold.
- In compact mode:
  - slightly smaller font
  - tighter padding
  - simpler visual styling
  - same data and same table structure
- Keep normal formatting for smaller PDF exports.

Success criteria:
- Full DB PDF becomes more practical while smaller PDFs keep better presentation.

## Phase 5: Validation and Regression Check
Status: pending

Tasks:
- Re-test:
  - full DB PDF
  - filtered PDF
  - full DB Excel
  - filtered Excel
  - ZIP export
- Confirm no regression in:
  - queue jobs
  - Level_1 grouping
  - Level_2 field selection
  - Level_3 filters
  - final downloaded file integrity
- Compare timing before and after optimization.

Success criteria:
- PDF becomes faster.
- Excel and ZIP remain stable.
- No feature behavior changes unless intentionally introduced.

## Safety Rules For Implementation
- PDF optimization will be done in small steps.
- After each phase, syntax checks will be run.
- If a phase causes regression, restore from the backup copies and rework the phase.
- Avoid broad refactors outside the PDF path unless strictly necessary.

## Rollback Plan
If a change causes problems, restore from:
- [asset.php.bak](D:\Laragon-root\www\pwd_asset\backup\pdf_optimization_2026-06-20\asset.php.bak)
- [exports.php.bak](D:\Laragon-root\www\pwd_asset\backup\pdf_optimization_2026-06-20\exports.php.bak)
- [board.php.bak](D:\Laragon-root\www\pwd_asset\backup\pdf_optimization_2026-06-20\board.php.bak)

## Next Step
Start Phase 1 only:
- add profiling
- run one full DB PDF
- inspect where time is actually spent
