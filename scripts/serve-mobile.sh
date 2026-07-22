#!/usr/bin/env bash

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "${SCRIPT_DIR}/.." && pwd)"
MOBILE_APP_DIR="${MOBILE_APP_DIR:-${PROJECT_ROOT}/../anderson-farm-fe}"
PORT="${API_PORT:-8000}"
HOST="${API_HOST:-0.0.0.0}"
SYNC_MOBILE_ENV="${SYNC_MOBILE_ENV:-1}"
WITH_QUEUE="${WITH_QUEUE:-0}"

usage() {
    cat <<'EOF'
Serve the Anderson Farm API for local mobile development.

Usage:
  bash scripts/serve-mobile.sh [options]

Options:
  --port <number>     API port (default: 8000)
  --host <address>    Bind address (default: 0.0.0.0)
  --mobile-dir <path> Path to Expo app (default: ../anderson-farm-fe)
  --no-sync           Do not update EXPO_PUBLIC_API_URL in the mobile .env
  --with-queue        Also run php artisan queue:listen
  -h, --help          Show this help message

Environment variables:
  API_PORT, API_HOST, MOBILE_APP_DIR, SYNC_MOBILE_ENV, WITH_QUEUE

Examples:
  npm run serve:mobile
  npm run serve:mobile:queue
  API_PORT=8080 bash scripts/serve-mobile.sh --no-sync
EOF
}

while [[ $# -gt 0 ]]; do
    case "$1" in
        --port)
            PORT="$2"
            shift 2
            ;;
        --host)
            HOST="$2"
            shift 2
            ;;
        --mobile-dir)
            MOBILE_APP_DIR="$2"
            shift 2
            ;;
        --no-sync)
            SYNC_MOBILE_ENV=0
            shift
            ;;
        --with-queue)
            WITH_QUEUE=1
            shift
            ;;
        -h | --help)
            usage
            exit 0
            ;;
        *)
            echo "Unknown option: $1" >&2
            usage >&2
            exit 1
            ;;
    esac
done

detect_lan_ip() {
    local ip=""

    if command -v powershell.exe >/dev/null 2>&1; then
        ip="$(powershell.exe -NoProfile -Command "(Get-NetIPAddress -AddressFamily IPv4 | Where-Object { \$_.InterfaceAlias -notmatch 'Loopback' -and \$_.IPAddress -notmatch '^169\.' } | Select-Object -First 1 -ExpandProperty IPAddress)" 2>/dev/null | tr -d '\r')"
    fi

    if [[ -z "${ip}" ]] && command -v ipconfig >/dev/null 2>&1; then
        ip="$(ipconfig 2>/dev/null | awk -F: '/IPv4/{gsub(/ /, "", $2); print $2; exit}')"
    fi

    if [[ -z "${ip}" ]] && command -v hostname >/dev/null 2>&1; then
        ip="$(hostname -I 2>/dev/null | awk '{print $1}')"
    fi

    if [[ -z "${ip}" ]]; then
        ip="127.0.0.1"
    fi

    printf '%s' "${ip}"
}

update_env_value() {
    local file="$1"
    local key="$2"
    local value="$3"

    if grep -q "^${key}=" "${file}"; then
        if [[ "$(uname -s)" == "Darwin" ]]; then
            sed -i '' "s|^${key}=.*|${key}=${value}|" "${file}"
        else
            sed -i "s|^${key}=.*|${key}=${value}|" "${file}"
        fi
    else
        printf '\n%s=%s\n' "${key}" "${value}" >> "${file}"
    fi
}

sync_mobile_env() {
    local api_url="$1"
    local env_file="${MOBILE_APP_DIR}/.env"
    local env_example="${MOBILE_APP_DIR}/.env.example"

    if [[ ! -d "${MOBILE_APP_DIR}" ]]; then
        echo "Mobile app directory not found: ${MOBILE_APP_DIR}"
        echo "Skipping EXPO_PUBLIC_API_URL sync."
        return
    fi

    if [[ ! -f "${env_file}" ]]; then
        if [[ -f "${env_example}" ]]; then
            cp "${env_example}" "${env_file}"
            echo "Created ${env_file} from .env.example"
        else
            touch "${env_file}"
            echo "Created empty ${env_file}"
        fi
    fi

    # Prefer Wi-Fi/LAN IP over Hyper-V virtual switch addresses for physical devices.
    local preferred_url="${api_url}"
    if command -v powershell.exe >/dev/null 2>&1; then
        local wifi_ip
        wifi_ip="$(powershell.exe -NoProfile -Command "(Get-NetIPAddress -AddressFamily IPv4 | Where-Object { \$_.InterfaceAlias -match 'Wi-Fi|WLAN' -and \$_.IPAddress -notmatch '^169\.' } | Select-Object -First 1 -ExpandProperty IPAddress)" 2>/dev/null | tr -d '\r')"
        if [[ -n "${wifi_ip}" ]]; then
            preferred_url="http://${wifi_ip}:${PORT}"
        fi
    fi

    update_env_value "${env_file}" "EXPO_PUBLIC_API_URL" "${preferred_url}/api/v1"
    echo "Updated mobile .env: EXPO_PUBLIC_API_URL=${preferred_url}/api/v1"
}

cd "${PROJECT_ROOT}"

if [[ ! -f artisan ]]; then
    echo "Laravel project root not found: ${PROJECT_ROOT}" >&2
    exit 1
fi

LAN_IP="$(detect_lan_ip)"
API_URL="http://${LAN_IP}:${PORT}"
ANDROID_EMULATOR_URL="http://10.0.2.2:${PORT}"
LOCAL_URL="http://127.0.0.1:${PORT}"

echo ""
echo "Anderson Farm API - Mobile Development Server"
echo "============================================="
echo "Project : ${PROJECT_ROOT}"
echo "Mobile  : ${MOBILE_APP_DIR}"
echo "Bind    : ${HOST}:${PORT}"
echo ""
echo "API URLs"
echo "  Physical device / same Wi-Fi : ${API_URL}"
echo "  Android emulator             : ${ANDROID_EMULATOR_URL}"
echo "  iOS simulator / local only   : ${LOCAL_URL}"
echo "  Health check                 : ${API_URL}/api/check"
echo ""

if [[ "${SYNC_MOBILE_ENV}" == "1" ]]; then
    sync_mobile_env "${API_URL}"
    echo ""
    echo "Restart Expo after .env changes: cd ../anderson-farm-fe && bun start -- --clear"
    echo ""
else
    echo "Skipped mobile .env sync (--no-sync)."
    echo ""
fi

SERVE_CMD=(php artisan serve --host="${HOST}" --port="${PORT}")

if [[ "${WITH_QUEUE}" == "1" ]]; then
    if ! command -v npx >/dev/null 2>&1; then
        echo "npx is required when using --with-queue." >&2
        exit 1
    fi

    exec npx concurrently \
        -c "#93c5fd,#fdba74" \
        --names "api,queue" \
        "${SERVE_CMD[*]}" \
        "php artisan queue:listen --tries=1"
fi

exec "${SERVE_CMD[@]}"
