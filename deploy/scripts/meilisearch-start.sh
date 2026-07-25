#!/bin/bash
set -euo pipefail

ENV_FILE="{{REMOTE_DIR}}/.env"
MEILI_BIN="{{MEILI_BIN}}"
MEILI_DATA="{{MEILI_DATA_PATH}}"

if [[ ! -f "$ENV_FILE" ]]; then
    echo "Missing .env: $ENV_FILE" >&2
    exit 1
fi

KEY=$(
    grep -E '^(MEILISEARCH_KEY|MEILI_MASTER_KEY)=' "$ENV_FILE" 2>/dev/null \
        | head -1 \
        | cut -d= -f2- \
        | tr -d '"' \
        | tr -d "'" \
        | xargs
)

if [[ -z "$KEY" ]]; then
    echo "MEILISEARCH_KEY (or MEILI_MASTER_KEY) not set in $ENV_FILE" >&2
    exit 1
fi

exec "$MEILI_BIN" --db-path "$MEILI_DATA" --env production --master-key "$KEY"
