#!/bin/bash
set -euo pipefail

TARGET="/var/www/zanburak-backend"
ZIP="/tmp/zanburak-backend-deploy.zip"

if [[ ! -f "$ZIP" ]]; then
    echo "Zip not found: $ZIP"
    exit 1
fi

mkdir -p "$TARGET"

echo "Cleaning old backend files..."
find "$TARGET" -mindepth 1 -maxdepth 1 -exec rm -rf {} +

echo "Extracting zip..."
unzip -oq "$ZIP" -d "$TARGET"

if [[ -f "$TARGET/production-env.txt" ]]; then
    echo "Applying production-env.txt as .env"
    cp "$TARGET/production-env.txt" "$TARGET/.env"
else
    echo "WARNING: production-env.txt not found in package"
fi

if id www-data >/dev/null 2>&1; then
    chown -R www-data:www-data "$TARGET/storage" "$TARGET/bootstrap/cache" "$TARGET/database" || true
    chown www-data:www-data "$TARGET/.env" 2>/dev/null || true
fi

chmod -R ug+rwx "$TARGET/storage" "$TARGET/bootstrap/cache" 2>/dev/null || true
chmod -R ug+rwX "$TARGET/database" 2>/dev/null || true

rm -f "$ZIP"

echo "Backend deploy OK - $(date '+%Y-%m-%d %H:%M:%S')"