#!/bin/bash
set -euo pipefail

TARGET="/var/www/zanburak-backend"
ZIP="/tmp/zanburak-backend-deploy.zip"
IP2_DIR="$TARGET/database/ip2location"
IP2_BACKUP_DIR=""

if [[ ! -f "$ZIP" ]]; then
    echo "Zip not found: $ZIP"
    exit 1
fi

mkdir -p "$TARGET"

# Keep large geo BIN across deploy (excluded from zip to keep upload small)
if [[ -d "$IP2_DIR" ]] && compgen -G "$IP2_DIR/*.BIN" > /dev/null; then
    IP2_BACKUP_DIR="$(mktemp -d /tmp/zanburak-ip2-XXXXXX)"
    echo "Backing up ip2location BIN files..."
    cp -a "$IP2_DIR"/*.BIN "$IP2_BACKUP_DIR/" 2>/dev/null || true
fi

echo "Cleaning old backend files..."
find "$TARGET" -mindepth 1 -maxdepth 1 -exec rm -rf {} +

echo "Extracting zip..."
unzip -oq "$ZIP" -d "$TARGET"

if [[ -n "$IP2_BACKUP_DIR" ]]; then
    mkdir -p "$IP2_DIR"
    restored=0
    for f in "$IP2_BACKUP_DIR"/*.BIN; do
        [[ -f "$f" ]] || continue
        base="$(basename "$f")"
        if [[ ! -f "$IP2_DIR/$base" ]]; then
            mv "$f" "$IP2_DIR/$base"
            restored=1
            echo "Restored $base"
        fi
    done
    rm -rf "$IP2_BACKUP_DIR"
    if [[ "$restored" -eq 0 ]]; then
        echo "ip2location BIN already present in package or nothing to restore"
    fi
elif ! compgen -G "$IP2_DIR/*.BIN" > /dev/null; then
    echo "WARNING: no IP2LOCATION.BIN on server — upload database/ip2location/*.BIN once if geo lookup is needed"
fi

if [[ -f "$TARGET/production-env.txt" ]]; then
    echo "Applying production-env.txt as .env"
    cp "$TARGET/production-env.txt" "$TARGET/.env"
else
    echo "WARNING: production-env.txt not found in package"
fi

# Zip excludes writable runtime dirs; recreate them before config/view cache.
mkdir -p \
    "$TARGET/storage/framework/cache/data" \
    "$TARGET/storage/framework/sessions" \
    "$TARGET/storage/framework/views" \
    "$TARGET/storage/framework/testing" \
    "$TARGET/storage/logs" \
    "$TARGET/storage/app/public" \
    "$TARGET/bootstrap/cache"

if id www-data >/dev/null 2>&1; then
    chown -R www-data:www-data "$TARGET/storage" "$TARGET/bootstrap/cache" "$TARGET/database" || true
    chown www-data:www-data "$TARGET/.env" 2>/dev/null || true
fi

chmod -R ug+rwx "$TARGET/storage" "$TARGET/bootstrap/cache" 2>/dev/null || true
chmod -R ug+rwX "$TARGET/database" 2>/dev/null || true

rm -f "$ZIP"

echo "Backend deploy OK - $(date '+%Y-%m-%d %H:%M:%S')"