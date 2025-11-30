# Documentation Workbench

## Sources

- `markdown/main-document.md` – Canonical source for the graduation report.
- `markdown/outline.md` – Ethiopian university-aligned table of contents.
- `latex/main.tex` – Alternative LaTeX layout.

## Export Workflow

1. Ensure Pandoc is installed locally (`pandoc --version`).
2. From the repository root, run:
   ```bash
   pandoc docs/markdown/main-document.md \
     --from markdown \
     --to docx \
     --resource-path=docs/markdown \
     --output docs/exports/library-management-system.docx
   ```
3. Review the generated DOCX and adjust academic formatting (font: Times New Roman 12pt, 1.5 spacing) directly if required by your faculty.

> Note: Replace placeholders (names, university, supervisor) before exporting.
