#!/usr/bin/env bash
# Raise messenger/media upload limits to ~50MB on Ubuntu (nginx + PHP-FPM + Laravel .env).
set -euo pipefail

LIMIT="55M"
ENV_FILE="/var/www/zanburak-backend/.env"
NGINX_API="/etc/nginx/sites-available/api.zanburak.ir"
PHP_VER="$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;' 2>/dev/null || echo '8.3')"
PHP_INI_FPM="/etc/php/${PHP_VER}/fpm/php.ini"
PHP_INI_CLI="/etc/php/${PHP_VER}/cli/php.ini"
PHP_FPM_POOL="/etc/php/${PHP_VER}/fpm/pool.d/www.conf"

echo "==> Target limit: ${LIMIT} (PHP ${PHP_VER})"

set_ini_value() {
  local file="$1" key="$2" value="$3"
  if [[ ! -f "$file" ]]; then
    echo "SKIP missing: $file"
    return 0
  fi
  if grep -qE "^[;[:space:]]*${key}[[:space:]]*=" "$file"; then
    sed -i -E "s|^[;[:space:]]*${key}[[:space:]]*=.*|${key} = ${value}|" "$file"
  else
    printf '\n%s = %s\n' "$key" "$value" >> "$file"
  fi
  echo "SET ${key}=${value} in ${file}"
}

# --- PHP ini (FPM + CLI) ---
for ini in "$PHP_INI_FPM" "$PHP_INI_CLI"; do
  set_ini_value "$ini" "upload_max_filesize" "$LIMIT"
  set_ini_value "$ini" "post_max_size" "$LIMIT"
done

# Also pin in FPM pool so pool overrides cannot shrink the limit.
if [[ -f "$PHP_FPM_POOL" ]]; then
  for pair in "upload_max_filesize=${LIMIT}" "post_max_size=${LIMIT}"; do
    key="${pair%%=*}"
    val="${pair#*=}"
    if grep -qE "^php_admin_value\[${key}\]" "$PHP_FPM_POOL"; then
      sed -i -E "s|^php_admin_value\[${key}\].*|php_admin_value[${key}] = ${val}|" "$PHP_FPM_POOL"
    elif grep -qE "^php_value\[${key}\]" "$PHP_FPM_POOL"; then
      sed -i -E "s|^php_value\[${key}\].*|php_admin_value[${key}] = ${val}|" "$PHP_FPM_POOL"
    else
      printf '\nphp_admin_value[%s] = %s\n' "$key" "$val" >> "$PHP_FPM_POOL"
    fi
    echo "SET php_admin_value[${key}]=${val} in ${PHP_FPM_POOL}"
  done
fi

# --- nginx: client_max_body_size on API vhost ---
if [[ -f "$NGINX_API" ]]; then
  if grep -qE 'client_max_body_size' "$NGINX_API"; then
    sed -i -E "s|client_max_body_size[[:space:]]+[^;]+;|client_max_body_size ${LIMIT};|g" "$NGINX_API"
  else
    # Insert into the first HTTPS server block after server_name.
    awk -v lim="$LIMIT" '
      BEGIN { done=0 }
      {
        print
        if (!done && $0 ~ /listen[[:space:]]+443/ ) { https=1 }
        if (!done && https && $0 ~ /server_name/ ) {
          print "    client_max_body_size " lim ";"
          done=1
        }
      }
    ' "$NGINX_API" > "${NGINX_API}.tmp" && mv "${NGINX_API}.tmp" "$NGINX_API"
  fi
  echo "SET client_max_body_size ${LIMIT} in ${NGINX_API}"
else
  echo "WARN: missing ${NGINX_API}"
fi

# Global fallback in nginx.conf http{} if somehow site misses it
if [[ -f /etc/nginx/nginx.conf ]] && ! grep -qE 'client_max_body_size' /etc/nginx/nginx.conf; then
  sed -i -E "/http[[:space:]]*\{/a\\    client_max_body_size ${LIMIT};" /etc/nginx/nginx.conf
  echo "SET client_max_body_size ${LIMIT} in /etc/nginx/nginx.conf"
fi

# --- Laravel .env messenger media caps (KB) ---
if [[ -f "$ENV_FILE" ]]; then
  ensure_env() {
    local key="$1" val="$2"
    if grep -qE "^${key}=" "$ENV_FILE"; then
      sed -i -E "s|^${key}=.*|${key}=${val}|" "$ENV_FILE"
    else
      printf '\n%s=%s\n' "$key" "$val" >> "$ENV_FILE"
    fi
    echo "SET ${key}=${val} in .env"
  }
  ensure_env MESSENGER_MAX_PHOTO_KB 51200
  ensure_env MESSENGER_MAX_VIDEO_KB 51200
  ensure_env MESSENGER_MAX_AUDIO_KB 51200
  ensure_env MESSENGER_MAX_VOICE_KB 51200
fi

# --- Apply ---
nginx -t
systemctl reload nginx
systemctl restart "php${PHP_VER}-fpm"

if [[ -x /var/www/zanburak-backend/artisan ]]; then
  sudo -u www-data /usr/bin/php /var/www/zanburak-backend/artisan config:clear || true
fi

echo "==> Verify"
php -i | grep -E 'upload_max_filesize|post_max_size' | head -n 4
# FPM effective values via php-fpm -i if available
if command -v "php-fpm${PHP_VER}" >/dev/null 2>&1; then
  "php-fpm${PHP_VER}" -i 2>/dev/null | grep -E 'upload_max_filesize|post_max_size' | head -n 4 || true
fi
grep -n 'client_max_body_size' "$NGINX_API" || true
grep -E 'MESSENGER_MAX_' "$ENV_FILE" || true
echo "DONE"
