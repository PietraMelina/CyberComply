#!/usr/bin/env bash
set -euo pipefail

ROOT="/Users/pietramelina/Documents/Documento de Requisitos Funcionais e Arquitetura – Cyber Comply"
BACKEND_DIR="$ROOT/cybercomply-backend"
FRONTEND_DIR="$ROOT/cybercomply-frontend"

BACKEND_HOST="${BACKEND_HOST:-127.0.0.1}"
BACKEND_PORT="${BACKEND_PORT:-8000}"
FRONTEND_PORT="${FRONTEND_PORT:-3000}"

cleanup() {
  local code=$?
  if [[ -n "${BACK_PID:-}" ]]; then kill "$BACK_PID" >/dev/null 2>&1 || true; fi
  if [[ -n "${FRONT_PID:-}" ]]; then kill "$FRONT_PID" >/dev/null 2>&1 || true; fi
  wait >/dev/null 2>&1 || true
  exit "$code"
}
trap cleanup INT TERM EXIT

echo "[dev-all] Starting backend on http://$BACKEND_HOST:$BACKEND_PORT"
(
  cd "$BACKEND_DIR"
  php artisan optimize:clear >/dev/null
  php artisan serve --host="$BACKEND_HOST" --port="$BACKEND_PORT" 2>&1 | sed -u 's/^/[backend] /'
) &
BACK_PID=$!

sleep 2

echo "[dev-all] Starting frontend on http://localhost:$FRONTEND_PORT"
(
  cd "$FRONTEND_DIR"
  PORT="$FRONTEND_PORT" npm run dev 2>&1 | sed -u 's/^/[frontend] /'
) &
FRONT_PID=$!

echo "[dev-all] Running both services. Press Ctrl+C to stop both."

wait -n "$BACK_PID" "$FRONT_PID"
