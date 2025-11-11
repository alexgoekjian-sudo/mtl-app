#!/usr/bin/env python3
"""
Lightweight import adapter (dry-run) for Trello and Courses CSVs.
- Reads CSVs from the imports folder
- Applies manifest-driven mappings (simple mapping)
- Outputs normalized JSON files and a validation report

Usage:
  python import_adapter.py --trello Trello_Export_MTL.csv --courses Courses_from_Google_Sheets.csv --outdir out --mode dryrun

This script avoids third-party dependencies for portability. It's intentionally minimal.
"""

import csv
import json
import os
import re
import argparse
from datetime import datetime


def load_manifest(path):
    with open(path, "r", encoding="utf-8") as f:
        return json.load(f)


def parse_date(s):
    # Try common US M/D/YYYY or ISO formats; return ISO date string or None
    if not s:
        return None
    s = s.strip()
    for fmt in ("%m/%d/%Y", "%m/%d/%y", "%Y-%m-%d", "%d/%m/%Y"):
        try:
            return datetime.strptime(s, fmt).date().isoformat()
        except Exception:
            pass
    # handle D.MM.YYYY or similar (from Course Full Name dates)
    m = re.search(r"(\d{1,2}\.\d{1,2}\.\d{4})", s)
    if m:
        try:
            d = datetime.strptime(m.group(1), "%d.%m.%Y").date().isoformat()
            return d
        except Exception:
            pass
    return None


def normalize_phone(s):
    if not s:
        return None
    digits = re.sub(r"[^0-9+]", "", s)
    # naive: if starts with 00 -> +
    if digits.startswith("00"):
        digits = "+" + digits[2:]
    # ensure plus prefix if country code present
    if digits.startswith("+") or len(digits) > 8:
        return digits
    return digits


def normalize_email(s):
    if not s:
        return None
    s = s.strip()
    if re.match(r"^[^@\s]+@[^@\s]+\.[^@\s]+$", s):
        return s.lower()
    return s


def transform_row(row, manifest):
    out = {}
    for src, target in manifest.get("mappings", {}).items():
        val = row.get(src, "").strip()
        if isinstance(target, dict):
            to = target.get("to")
            ttype = target.get("type")
            if ttype == "date":
                out[to] = parse_date(val)
            elif ttype == "number":
                try:
                    out[to] = float(val) if val else None
                except Exception:
                    out[to] = None
            else:
                out[to] = val or None
        else:
            out[target] = val or None
    return out


def process_courses(csv_path, manifest, outdir):
    courses = []
    report = []
    # open CSV with encoding fallback (utf-8, latin-1, cp1252)
    f = None
    for enc in ('utf-8-sig', 'utf-8', 'latin-1', 'cp1252'):
        try:
            f = open(csv_path, newline='', encoding=enc)
            reader = csv.DictReader(f)
            # touch fieldnames to trigger potential Unicode errors early
            _ = reader.fieldnames
            break
        except Exception:
            if f:
                try:
                    f.close()
                except Exception:
                    pass
            f = None
            reader = None
    if reader is None:
        raise RuntimeError(f"Unable to open CSV file {csv_path} with supported encodings")
    for i, row in enumerate(reader, start=1):
        transformed = transform_row(row, manifest)
        # normalize specific fields
        if transformed.get('time_range'):
            transformed['time_range'] = transformed['time_range']
        if transformed.get('days'):
            transformed['days'] = [d.strip() for d in transformed['days'].split(',') if d.strip()]
        # parse dates
        if 'start_date' in transformed and transformed['start_date']:
            transformed['start_date'] = parse_date(transformed['start_date'])
        if 'end_date' in transformed and transformed['end_date']:
            transformed['end_date'] = parse_date(transformed['end_date'])
        # price numeric already handled
        courses.append({'source_row': i, 'course_offering': transformed, 'import_status': 'ok'})
    out_path = os.path.join(outdir, 'courses_normalized.json')
    with open(out_path, 'w', encoding='utf-8') as wf:
        json.dump(courses, wf, indent=2, ensure_ascii=False)
    return courses


def process_trello(csv_path, manifest, courses_index_keys, outdir):
    normalized = []
    report = []
    # open CSV with encoding fallback (utf-8, latin-1, cp1252)
    f = None
    for enc in ('utf-8-sig', 'utf-8', 'latin-1', 'cp1252'):
        try:
            f = open(csv_path, newline='', encoding=enc)
            reader = csv.DictReader(f)
            _ = reader.fieldnames
            break
        except Exception:
            if f:
                try:
                    f.close()
                except Exception:
                    pass
            f = None
            reader = None
    if reader is None:
        raise RuntimeError(f"Unable to open CSV file {csv_path} with supported encodings")
    for i, row in enumerate(reader, start=1):
        transformed = transform_row(row, manifest)
        # normalize contact fields
        if transformed.get('email'):
            transformed['email'] = normalize_email(transformed['email'])
        if transformed.get('phone'):
            transformed['phone'] = normalize_phone(transformed['phone'])
        if transformed.get('languages'):
            transformed['languages'] = [l.strip() for l in transformed['languages'].split(',') if l.strip()]
        # attempt to link to course_key via simple slug/contains check
        course_name = transformed.get('course_name') or ''
        linked = None
        for key in courses_index_keys:
            if key and key.lower() in course_name.lower():
                linked = key
                break
        transformed['linked_course_key'] = linked
        normalized.append({'source_row': i, 'student': transformed, 'import_status': 'ok' if transformed.get('email') or transformed.get('phone') else 'warning:missing_contact'})
    out_path = os.path.join(outdir, 'trello_normalized.json')
    with open(out_path, 'w', encoding='utf-8') as wf:
        json.dump(normalized, wf, indent=2, ensure_ascii=False)
    return normalized


def build_course_index(courses_json_path):
    # read courses_normalized.json if exists, otherwise return an empty list
    if not os.path.exists(courses_json_path):
        return []
    with open(courses_json_path, 'r', encoding='utf-8') as f:
        rows = json.load(f)
    keys = []
    for r in rows:
        c = r.get('course_offering') or {}
        k = c.get('course_key') or c.get('course_full_name')
        if k:
            keys.append(k)
    return keys


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument('--trello', help='Trello CSV file path', required=True)
    parser.add_argument('--courses', help='Courses CSV file path', required=True)
    parser.add_argument('--outdir', help='Output directory', default='./out')
    parser.add_argument('--mode', choices=['dryrun', 'apply'], default='dryrun')
    args = parser.parse_args()

    os.makedirs(args.outdir, exist_ok=True)

    base = os.path.dirname(__file__)
    manifest_trello = os.path.join(base, 'manifest.trello.json')
    manifest_courses = os.path.join(base, 'manifest.courses.json')

    trello_manifest = load_manifest(manifest_trello)
    courses_manifest = load_manifest(manifest_courses)

    # process courses first
    courses = process_courses(args.courses, courses_manifest, args.outdir)
    courses_path = os.path.join(args.outdir, 'courses_normalized.json')

    # build course index for linking
    course_keys = build_course_index(courses_path)

    # process trello
    trello_norm = process_trello(args.trello, trello_manifest, course_keys, args.outdir)

    # validation report: simple checks
    report = {'courses_rows': len(courses), 'trello_rows': len(trello_norm), 'issues': []}
    # check for unmatched course links
    unmatched = [r for r in trello_norm if not r['student'].get('linked_course_key')]
    if unmatched:
        report['issues'].append({'unmatched_course_count': len(unmatched), 'sample_unmatched': unmatched[:5]})

    with open(os.path.join(args.outdir, 'validation_report.json'), 'w', encoding='utf-8') as rf:
        json.dump(report, rf, indent=2, ensure_ascii=False)

    print('Dry-run complete. Outputs in:', os.path.abspath(args.outdir))

if __name__ == '__main__':
    main()
