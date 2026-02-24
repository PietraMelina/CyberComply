#!/usr/bin/env bash
set -euo pipefail

BASE_URL="${BASE_URL:-http://127.0.0.1:8000}"
EMAIL="${EMAIL:-master@cybercomply.local}"
PASSWORD="${PASSWORD:-ChangeMe!123}"

command -v curl >/dev/null || { echo "Missing curl"; exit 1; }
command -v jq >/dev/null || { echo "Missing jq"; exit 1; }

LOGIN_JSON=$(curl -sS -X POST "$BASE_URL/api/auth/login" -H 'Content-Type: application/json' -d "{\"email\":\"$EMAIL\",\"password\":\"$PASSWORD\"}")
TOKEN=$(echo "$LOGIN_JSON" | jq -r '.access_token')
[[ -n "$TOKEN" && "$TOKEN" != "null" ]] || { echo "Login failed"; echo "$LOGIN_JSON"; exit 1; }

echo "[1/3] List audit logs"
LIST_JSON=$(curl -sS "$BASE_URL/api/audit-logs?per_page=5" -H "Authorization: Bearer $TOKEN")
FIRST_ID=$(echo "$LIST_JSON" | jq -r '.data[0].id')
[[ -n "$FIRST_ID" && "$FIRST_ID" != "null" ]] || { echo "$LIST_JSON"; exit 1; }

echo "[2/3] Audit log detail"
DETAIL_JSON=$(curl -sS "$BASE_URL/api/audit-logs/$FIRST_ID" -H "Authorization: Bearer $TOKEN")
echo "$DETAIL_JSON" | jq '.id, .source, .action'

echo "[3/3] Export CSV"
HTTP_CODE=$(curl -sS -o /tmp/audit_export_$$.csv -w '%{http_code}' "$BASE_URL/api/audit-logs/export?source=all" -H "Authorization: Bearer $TOKEN")
[[ "$HTTP_CODE" == "200" ]] || { echo "Export failed HTTP $HTTP_CODE"; exit 1; }
head -n 1 /tmp/audit_export_$$.csv

echo "Phase 4 smoke test finished."
