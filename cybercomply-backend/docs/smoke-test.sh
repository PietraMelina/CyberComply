#!/usr/bin/env bash
set -euo pipefail

BASE_URL="${BASE_URL:-http://127.0.0.1:8000}"
EMAIL="${EMAIL:-master@cybercomply.local}"
PASSWORD="${PASSWORD:-ChangeMe!123}"
TECH_EMAIL="${TECH_EMAIL:-tecnico.smoke+$(date +%s)@cybercomply.local}"

require_bin() {
  command -v "$1" >/dev/null 2>&1 || { echo "Missing dependency: $1"; exit 1; }
}

require_bin curl
require_bin jq

echo "[1/5] Login"
LOGIN_JSON=$(curl -sS -X POST "$BASE_URL/api/auth/login" \
  -H 'Content-Type: application/json' \
  -d "{\"email\":\"$EMAIL\",\"password\":\"$PASSWORD\"}")
TOKEN=$(echo "$LOGIN_JSON" | jq -r '.access_token')

if [[ -z "$TOKEN" || "$TOKEN" == "null" ]]; then
  echo "Login failed:" && echo "$LOGIN_JSON"
  exit 1
fi

echo "[2/5] /auth/me"
curl -sS "$BASE_URL/api/auth/me" -H "Authorization: Bearer $TOKEN" | jq .

echo "[3/5] Create client"
CLIENT_JSON=$(curl -sS -X POST "$BASE_URL/api/clients" \
  -H "Authorization: Bearer $TOKEN" \
  -H 'Content-Type: application/json' \
  -d '{"type":"PRIV","name":"Cliente Smoke Test","vat_number":"123456789"}')
CLIENT_ID=$(echo "$CLIENT_JSON" | jq -r '.id')
echo "client_id=$CLIENT_ID"

echo "[4/5] Create user under client"
USER_JSON=$(curl -sS -X POST "$BASE_URL/api/users" \
  -H "Authorization: Bearer $TOKEN" \
  -H 'Content-Type: application/json' \
  -d "{\"email\":\"$TECH_EMAIL\",\"password\":\"Strong!Pass123\",\"role_id\":5,\"client_id\":\"$CLIENT_ID\",\"accepted_terms_version\":\"v1\",\"company_code\":\"CYBR\"}")
USER_ID=$(echo "$USER_JSON" | jq -r '.id')
echo "user_id=$USER_ID"

echo "[5/5] Enable MFA"
curl -sS -X POST "$BASE_URL/api/users/$USER_ID/mfa/enable" \
  -H "Authorization: Bearer $TOKEN" | jq .

echo "Smoke test finished."
