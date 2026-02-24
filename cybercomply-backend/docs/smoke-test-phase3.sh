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

echo "[1/6] Create client"
CLIENT_JSON=$(curl -sS -X POST "$BASE_URL/api/clients" -H "Authorization: Bearer $TOKEN" -H 'Content-Type: application/json' -d '{"type":"PRIV","name":"Cliente Phase3 Test","vat_number":"987654321"}')
CLIENT_ID=$(echo "$CLIENT_JSON" | jq -r '.id')

echo "[2/6] Create module"
MODULE_JSON=$(curl -sS -X POST "$BASE_URL/api/modules" -H "Authorization: Bearer $TOKEN" -H 'Content-Type: application/json' -d "{\"client_id\":\"$CLIENT_ID\",\"code\":\"ISO27001-A5\",\"name\":\"Controles A5\"}")
MODULE_ID=$(echo "$MODULE_JSON" | jq -r '.id')

echo "[3/6] Create question"
QUESTION_JSON=$(curl -sS -X POST "$BASE_URL/api/modules/$MODULE_ID/questions" -H "Authorization: Bearer $TOKEN" -H 'Content-Type: application/json' -d "{\"client_id\":\"$CLIENT_ID\",\"question_text\":\"Existe política formal?\",\"weight\":1.5,\"order_index\":1}")
QUESTION_ID=$(echo "$QUESTION_JSON" | jq -r '.id')

echo "[4/6] Create response version"
RESPONSE_JSON=$(curl -sS -X POST "$BASE_URL/api/responses" -H "Authorization: Bearer $TOKEN" -H 'Content-Type: application/json' -d "{\"client_id\":\"$CLIENT_ID\",\"question_id\":$QUESTION_ID,\"status\":\"CONFORME\",\"comment\":\"OK\"}")
RESPONSE_ID=$(echo "$RESPONSE_JSON" | jq -r '.id')

echo "[5/6] Upload evidence"
PNG_FILE="/tmp/phase3_evidence_$$.png"
cat <<'B64' | base64 -d > "$PNG_FILE"
iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO7ZxfoAAAAASUVORK5CYII=
B64
EVIDENCE_JSON=$(curl -sS -X POST "$BASE_URL/api/evidences" -H "Authorization: Bearer $TOKEN" -F "client_id=$CLIENT_ID" -F "response_id=$RESPONSE_ID" -F "file=@$PNG_FILE;type=image/png")
EVIDENCE_TOKEN=$(echo "$EVIDENCE_JSON" | jq -r '.internal_token')

if [[ -z "$EVIDENCE_TOKEN" || "$EVIDENCE_TOKEN" == "null" ]]; then
  echo "$EVIDENCE_JSON"
  exit 1
fi

echo "[6/6] Download evidence"
HTTP_CODE=$(curl -sS -o /tmp/phase3_download_$$.bin -w '%{http_code}' -H "Authorization: Bearer $TOKEN" "$BASE_URL/api/evidences/$EVIDENCE_TOKEN")
[[ "$HTTP_CODE" == "200" ]] || { echo "Download failed with HTTP $HTTP_CODE"; exit 1; }

echo "Phase 3 smoke test finished. client_id=$CLIENT_ID module_id=$MODULE_ID question_id=$QUESTION_ID response_id=$RESPONSE_ID evidence_token=$EVIDENCE_TOKEN"
