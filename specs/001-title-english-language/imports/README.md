Imports for feature: English language school management

Purpose
-------
This folder holds sample and (optionally) real export files that will be used for data migration during the initial rollout. It is scoped to the feature branch `001-title-english-language` so that import samples and mappings live alongside the spec.

Security and privacy notes
--------------------------
- DO NOT commit production exports containing real PII (names, emails, phone numbers) into source control unless they are sanitized or you explicitly consent to storing them in the repo.
- If you need to provide full production exports, use a secure transfer (S3 with restricted access, a private shared drive, or an encrypted archive outside the repo).
- Sample files in this folder are sanitized and safe to commit.

What to put here
-----------------
- Trello exports (JSON or CSV) from the boards currently used for student/class management.
- Google Sheets CSV exports for course schedules, attendance sheets, and student lists.
- A manifest.json (or manifest.template.json) describing what each file contains and a suggested mapping.

Naming conventions (recommended)
--------------------------------
- trello-<board-name>-YYYYMMDD.json or .csv
- sheets-<purpose>-YYYYMMDD.csv (e.g. sheets-courses-20251111.csv)
- manifest.template.json (mapping guidance)

Workflow
--------
1. Add sanitized sample exports to this folder (or upload full exports via a secure channel and tell us where to retrieve them).
2. I will draft a mapping and an import adapter that transforms these files into the app's entities (Lead, Student, CourseOffering, Session, Enrollment, AttendanceRecord, Invoice/Payment).
3. We'll run imports in a staging environment (dry-run first) and review validation reports before any production migration.

If you want me to scaffold import scripts or the mapping file, say so and I will generate them next.
