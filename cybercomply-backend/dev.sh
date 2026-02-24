#!/usr/bin/env bash
set -euo pipefail

echo "Starting CyberComply (Laravel + Inertia) on one terminal..."

if lsof -tiTCP:8000 -sTCP:LISTEN >/dev/null 2>&1; then
  echo "Error: port 8000 is already in use."
  echo "Run: lsof -tiTCP:8000 -sTCP:LISTEN | xargs -r kill -9"
  exit 1
fi

if lsof -tiTCP:5173 -sTCP:LISTEN >/dev/null 2>&1; then
  echo "Error: port 5173 is already in use."
  echo "Run: lsof -tiTCP:5173 -sTCP:LISTEN | xargs -r kill -9"
  exit 1
fi

php artisan serve --host=127.0.0.1 --port=8000 &
LARAVEL_PID=$!

npm run dev -- --host 127.0.0.1 --port 5173 &
VITE_PID=$!

cleanup() {
  echo ""
  echo "Stopping services..."
  kill "$LARAVEL_PID" 2>/dev/null || true
  kill "$VITE_PID" 2>/dev/null || true
}

trap cleanup EXIT INT TERM

echo "App: http://127.0.0.1:8000"
echo "Do not open Vite directly. Use only: http://127.0.0.1:8000"
wait
