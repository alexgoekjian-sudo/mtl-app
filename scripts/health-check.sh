#!/usr/bin/env bash
# scripts/health-check.sh
# Simple health probe for the app /status endpoint.
# Writes failures (and OK) to storage/logs/health-check.log

set -u

APP_ROOT="/home/u5021d9810/domains/mixtreelangdb.nl/mtl_app"
URL="https://mixtreelangdb.nl/status"
LOG_DIR="$APP_ROOT/storage/logs"
LOG_FILE="$LOG_DIR/health-check.log"
TIMESTAMP="$(date -u +'%Y-%m-%dT%H:%M:%SZ')"

# Ensure log directory exists and is writable (best-effort)
mkdir -p "$LOG_DIR" 2>/dev/null || true
# Run request (use curl with SSL verify disabled to match your existing tests)
HTTP_CODE=$(curl -sS -o /dev/null -w "%{http_code}" -k "$URL" 2>/dev/null || echo "000")

if [ "$HTTP_CODE" != "200" ]; then
  echo "$TIMESTAMP FAIL $HTTP_CODE" >> "$LOG_FILE"
  exit 1
else
  echo "$TIMESTAMP OK" >> "$LOG_FILE"
  exit 0
fi
