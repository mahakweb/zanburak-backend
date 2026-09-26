#!/bin/bash
set -euo pipefail

# Replace vendor/ from uploaded vendor zip (does not wipe the rest of the app).
TARGET="/var/www/zanburak-backend"
ZIP="/tmp/zanburak-backend-vendor.zip"
WEB_USER="www-data"

if [[ ! -f "$ZIP" ]]; then
    echo "Vendor zip not found: $ZIP"
    exit 1
fi

mkdir -p "$TARGET"

echo "Replacing vendor/ ..."
rm -rf "$TARGET/vendor"
mkdir -p "$TARGET"
unzip -oq "$ZIP" -d "$TARGET"

if [[ ! -f "$TARGET/vendor/autoload.php" ]]; then
    echo "ERROR: vendor/autoload.php missing after extract"
    exit 1
fi

LOCK_FILE="$TARGET/composer.lock"
LOCK_HASH_FILE="$TARGET/vendor/.deploy-composer-lock.sha256"
if [[ -f "$LOCK_FILE" ]]; then
    sha256sum "$LOCK_FILE" | awk '{print $1}' > "$LOCK_HASH_FILE"
    echo "Recorded composer.lock hash"
fi

if id "$WEB_USER" >/dev/null 2>&1; then
    chown -R "${WEB_USER}:${WEB_USER}" "$TARGET/vendor" || true
fi

rm -f "$ZIP"
echo "Vendor deploy OK - $(date '+%Y-%m-%d %H:%M:%S')"
