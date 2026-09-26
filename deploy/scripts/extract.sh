#!/bin/bash
set -euo pipefail

# MODE: app  = keep existing vendor (restore after wipe)
#       full = vendor comes from zip (do not restore old vendor)
MODE="${1:-app}"

TARGET="/var/www/zanburak-backend"
ZIP="/tmp/zanburak-backend-deploy.zip"
BACKUP_ROOT=""
WEB_USER="www-data"

if [[ ! -f "$ZIP" ]]; then
    echo "Zip not found: $ZIP"
    exit 1
fi

if [[ "$MODE" != "app" && "$MODE" != "full" ]]; then
    echo "Invalid mode: $MODE (use app|full)"
    exit 1
fi

echo "Extract mode: $MODE"
mkdir -p "$TARGET"
BACKUP_ROOT="$(mktemp -d /tmp/zanburak-deploy-bak-XXXXXX)"

backup_path() {
    local rel="$1"
    local src="$TARGET/$rel"
    if [[ -e "$src" ]]; then
        mkdir -p "$(dirname "$BACKUP_ROOT/$rel")"
        mv "$src" "$BACKUP_ROOT/$rel"
        echo "Backed up $rel"
    fi
}

restore_path() {
    local rel="$1"
    local bak="$BACKUP_ROOT/$rel"
    local dst="$TARGET/$rel"
    if [[ -e "$bak" ]]; then
        mkdir -p "$(dirname "$dst")"
        if [[ -e "$dst" ]]; then
            rm -rf "$dst"
        fi
        mv "$bak" "$dst"
        echo "Restored $rel"
    fi
}

# Always keep runtime / geo data across wipe
backup_path "database/ip2location"
backup_path "storage"

# App mode: keep vendor on server. Full mode: vendor is inside the zip.
if [[ "$MODE" == "app" ]]; then
    backup_path "vendor"
fi

echo "Cleaning old backend files..."
find "$TARGET" -mindepth 1 -maxdepth 1 -exec rm -rf {} +

echo "Extracting zip..."
unzip -oq "$ZIP" -d "$TARGET"

if [[ "$MODE" == "app" ]]; then
    restore_path "vendor"
fi
restore_path "database/ip2location"
restore_path "storage"

if [[ -f "$TARGET/production-env.txt" ]]; then
    echo "Applying production-env.txt as .env"
    cp "$TARGET/production-env.txt" "$TARGET/.env"
else
    echo "WARNING: production-env.txt not found in package"
fi

mkdir -p \
    "$TARGET/storage/framework/cache/data" \
    "$TARGET/storage/framework/sessions" \
    "$TARGET/storage/framework/views" \
    "$TARGET/storage/framework/testing" \
    "$TARGET/storage/logs" \
    "$TARGET/storage/app/public" \
    "$TARGET/bootstrap/cache"

LOCK_FILE="$TARGET/composer.lock"
LOCK_HASH_FILE="$TARGET/vendor/.deploy-composer-lock.sha256"

stamp_lock_hash() {
    if [[ -f "$LOCK_FILE" ]]; then
        mkdir -p "$TARGET/vendor"
        sha256sum "$LOCK_FILE" | awk '{print $1}' > "$LOCK_HASH_FILE"
        echo "Recorded composer.lock hash"
    fi
}

if [[ ! -f "$TARGET/vendor/autoload.php" ]]; then
    echo "WARNING: vendor/autoload.php missing."
    echo "Upload vendor with menu [3], or run a full deploy [1]."
elif [[ ! -f "$LOCK_FILE" ]]; then
    echo "WARNING: composer.lock missing in package"
elif [[ ! -f "$LOCK_HASH_FILE" ]]; then
    # Vendor already present (typical after first app-only deploy) — just stamp hash.
    echo "Vendor present — recording lock hash (no composer required)"
    stamp_lock_hash
else
    NEW_HASH="$(sha256sum "$LOCK_FILE" | awk '{print $1}')"
    OLD_HASH="$(tr -d '[:space:]' < "$LOCK_HASH_FILE" || true)"
    if [[ "$NEW_HASH" != "$OLD_HASH" ]]; then
        echo "WARNING: composer.lock changed vs server vendor."
        echo "Use menu [3] (vendor upload) or [1] (full deploy) to refresh vendor."
        stamp_lock_hash
    else
        echo "vendor up to date (composer.lock unchanged)"
    fi
fi

if id "$WEB_USER" >/dev/null 2>&1; then
    chown -R "${WEB_USER}:${WEB_USER}" "$TARGET/storage" "$TARGET/bootstrap/cache" "$TARGET/database" || true
    chown "${WEB_USER}:${WEB_USER}" "$TARGET/.env" 2>/dev/null || true
fi

chmod -R ug+rwx "$TARGET/storage" "$TARGET/bootstrap/cache" 2>/dev/null || true
chmod -R ug+rwX "$TARGET/database" 2>/dev/null || true

rm -rf "$BACKUP_ROOT"
rm -f "$ZIP"

echo "Backend deploy OK ($MODE) - $(date '+%Y-%m-%d %H:%M:%S')"
