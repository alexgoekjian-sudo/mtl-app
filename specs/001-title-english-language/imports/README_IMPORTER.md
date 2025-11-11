Import adapter notes

Files in this folder:
- `Trello_Export_MTL.csv` (user-provided) — source of leads/students
- `Courses_from_Google_Sheets.csv` (user-provided) — source of course offerings
- `manifest.trello.json` — mapping manifest for Trello exports
- `manifest.courses.json` — mapping manifest for Courses CSV
- `import_adapter.py` — minimal adapter (dry-run mode) that transforms CSV rows to normalized JSON

How to use (dry-run):
- Review mapping manifests and adjust mappings if necessary.
- Run the adapter locally (Python 3.8+):

  python import_adapter.py --trello Trello_Export_MTL.csv --courses Courses_from_Google_Sheets.csv --outdir ./out --mode dryrun

Outputs (dry-run):
- `out/trello_normalized.json` — normalized student/lead records
- `out/courses_normalized.json` — normalized course offering records
- `out/validation_report.json` — row-level errors and warnings

The adapter uses simple normalization rules (date parsing, phone digits-only normalization, email format checks). It is intentionally lightweight and dependency-free so it runs without installing packages. If you prefer richer parsing (e.g. dateutil, phonenumbers), update `requirements.txt` and adapter accordingly.
