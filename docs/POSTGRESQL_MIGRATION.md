# PostgreSQL Migration Guide — Zanburak Backend

This document covers migrating the Zanburak Laravel 11 backend from MySQL to PostgreSQL, including local development on Windows and production deployment on Ubuntu 22.04.

---

## Summary of Code Changes

| Area | Change |
|------|--------|
| **Config** | Default connection is now `pgsql`; enhanced `config/database.php` |
| **Migrations** | JSON columns use `jsonb` on PostgreSQL via `MigrationColumnHelpers` |
| **Optimizations** | `2026_06_13_000001_postgresql_optimizations.php` adds `citext`, GIN indexes, B-tree indexes |
| **Queries** | MySQL-only functions replaced with `App\Support\SqlDialect` (works on both drivers) |
| **Enums** | Laravel `$table->enum()` → PostgreSQL `varchar` + CHECK constraint (no app changes) |
| **Case sensitivity** | `users.email` and `users.username` converted to `citext` on PostgreSQL |
| **Scout** | `SCOUT_DRIVER=database` uses `LIKE` — no MySQL FULLTEXT dependency |

### Files with dialect-aware SQL

- `app/Http/Controllers/Api/Admin/DashboardController.php`
- `app/Http/Controllers/Api/Admin/EngagementController.php`
- `app/Http/Controllers/Api/Admin/ViewController.php`
- `app/Http/Controllers/Api/Admin/SalesReportController.php`
- `app/Http/Controllers/Api/Admin/UserActivityReportController.php`
- `app/Http/Controllers/Api/Admin/ProjectController.php`
- `app/Listeners/Mission/Purchases/LoyalCustomerListener.php`
- `app/Listeners/Mission/CourseCompletions/OngoingLearningListener.php`

### New helpers

- `app/Support/SqlDialect.php` — cross-database date/time/string SQL fragments
- `app/Database/Schema/MigrationColumnHelpers.php` — `json` on MySQL, `jsonb` on PostgreSQL

---

## Environment Variables

```dotenv
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=zanburak
DB_USERNAME=zanburak
DB_PASSWORD=your_secure_password
DB_SSLMODE=prefer          # disable | allow | prefer | require
DB_SEARCH_PATH=public
DB_CHARSET=utf8
```

Remove MySQL-only variables (`DB_ENGINE`, `DB_SOCKET`, `MYSQL_ATTR_SSL_CA`).

---

## Local Development — Windows

### 1. Install PostgreSQL

**Option A — Official installer (recommended)**

1. Download PostgreSQL 16 from https://www.postgresql.org/download/windows/
2. Install with Stack Builder; note the `postgres` superuser password
3. Add `C:\Program Files\PostgreSQL\16\bin` to your PATH

**Option B — Docker**

```powershell
docker run --name zanburak-pg `
  -e POSTGRES_DB=zanburak `
  -e POSTGRES_USER=postgres `
  -e POSTGRES_PASSWORD=secret `
  -p 5432:5432 `
  -d postgres:16
```

### 2. Enable PHP PostgreSQL extension

In `php.ini`, uncomment or add:

```ini
extension=pdo_pgsql
extension=pgsql
```

Verify:

```powershell
php -m | findstr pgsql
```

### 3. Create database and user

```powershell
psql -U postgres
```

```sql
CREATE USER zanburak WITH PASSWORD 'your_password';
CREATE DATABASE zanburak OWNER zanburak ENCODING 'UTF8';
GRANT ALL PRIVILEGES ON DATABASE zanburak TO zanburak;
\c zanburak
GRANT ALL ON SCHEMA public TO zanburak;
GRANT CREATE ON SCHEMA public TO zanburak;
\q
```

### 4. Configure Laravel

Copy/update `.env` (see variables above), then:

```powershell
cd e:\zanburak\zanburak-backend
composer install
php artisan config:clear
php artisan migrate
php artisan db:seed   # if you use seeders
php artisan serve
```

### 5. Fresh install vs existing MySQL data

**Fresh install:** run `php artisan migrate` — all migrations are PostgreSQL-compatible.

**Migrating existing MySQL data:**

```powershell
# Export from MySQL
mysqldump -u root -p zanburak > zanburak_mysql.sql

# Convert with pgloader (install via Chocolatey or WSL)
pgloader mysql://root@localhost/zanburak pgsql://zanburak:password@localhost/zanburak

# Then run any new migrations
php artisan migrate
```

> pgloader handles most type conversions automatically. Review enum and JSON columns after import.

---

## Production Deployment — Ubuntu 22.04

### 1. Install PostgreSQL 16

```bash
sudo apt update
sudo apt install -y postgresql postgresql-contrib
sudo systemctl enable postgresql
sudo systemctl start postgresql
```

### 2. Create production database

```bash
sudo -u postgres psql
```

```sql
CREATE USER zanburak WITH PASSWORD 'STRONG_PASSWORD_HERE';
CREATE DATABASE zanburak OWNER zanburak ENCODING 'UTF8' LC_COLLATE='en_US.UTF-8' LC_CTYPE='en_US.UTF-8';
GRANT ALL PRIVILEGES ON DATABASE zanburak TO zanburak;
\c zanburak
GRANT ALL ON SCHEMA public TO zanburak;
ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL ON TABLES TO zanburak;
ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL ON SEQUENCES TO zanburak;
\q
```

### 3. Install PHP PostgreSQL extension

```bash
sudo apt install -y php8.2-pgsql php8.2-cli php8.2-fpm
sudo systemctl restart php8.2-fpm
```

### 4. PostgreSQL tuning (recommended)

Edit `/etc/postgresql/16/main/postgresql.conf`:

```conf
shared_buffers = 256MB          # 25% of RAM for dedicated DB server
effective_cache_size = 1GB
maintenance_work_mem = 128MB
work_mem = 16MB
random_page_cost = 1.1          # SSD
max_connections = 100
log_min_duration_statement = 500  # log slow queries > 500ms
```

Edit `/etc/postgresql/16/main/pg_hba.conf` — allow local app connections:

```conf
# TYPE  DATABASE   USER      ADDRESS       METHOD
local   zanburak   zanburak                scram-sha-256
host    zanburak   zanburak  127.0.0.1/32  scram-sha-256
```

```bash
sudo systemctl restart postgresql
```

### 5. Deploy application

```bash
cd /var/www/zanburak-backend
git pull origin main
composer install --no-dev --optimize-autoloader
cp production-env.txt .env   # ensure DB_CONNECTION=pgsql
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan migrate --force
php artisan queue:restart
sudo systemctl reload php8.2-fpm
sudo systemctl reload nginx
```

### 6. Migrate data from MySQL (production cutover)

```bash
# On MySQL server — export
mysqldump -u zanburak -p --single-transaction --routines zanburak > /tmp/zanburak_mysql.sql

# Install pgloader
sudo apt install -y pgloader

# Create pgloader config /tmp/migrate.load
cat > /tmp/migrate.load <<'EOF'
LOAD DATABASE
     FROM mysql://zanburak:MYSQL_PASS@127.0.0.1/zanburak
     INTO postgresql://zanburak:PG_PASS@127.0.0.1/zanburak

 WITH include drop, create tables, create indexes, reset sequences

 SET maintenance_work_mem to '256MB', work_mem to '128MB'

 CAST type datetime to timestamptz drop default drop not null using zero-dates-to-null,
      type date drop not null drop default using zero-dates-to-null

 ALTER SCHEMA 'zanburak' RENAME TO 'public';
EOF

pgloader /tmp/migrate.load

# Run post-migration Laravel migrations (citext, GIN indexes)
cd /var/www/zanburak-backend
php artisan migrate --force
```

### 7. Zero-downtime cutover checklist

- [ ] PostgreSQL provisioned and tuned
- [ ] `php artisan migrate` succeeds on empty PostgreSQL
- [ ] pgloader import verified (row counts match)
- [ ] `citext` extension active: `SELECT * FROM pg_extension WHERE extname = 'citext';`
- [ ] Analytics dashboards tested (date grouping, heatmaps)
- [ ] Auth login with mixed-case email works
- [ ] Messenger, payments, and course enrollment smoke-tested
- [ ] Update `.env` on all workers/queue processes
- [ ] Keep MySQL read-only for 48h rollback window

---

## PostgreSQL-Specific Features Added

### citext (case-insensitive email/username)

MySQL `utf8mb4_unicode_ci` comparisons are case-insensitive; PostgreSQL `varchar` is not. The optimization migration converts:

- `users.email` → `citext`
- `users.username` → `citext`

Existing `User::where('email', $email)` queries continue to work without code changes.

### JSONB + GIN indexes

JSON columns stored as `jsonb` on PostgreSQL with GIN indexes on:

| Table | Column |
|-------|--------|
| `paths` | `faqs` |
| `plans` | `features` |
| `missions` | `levels` |
| `questions` | `allowed_user_ids` |
| `messenger_events` | `payload` |
| `discount_conditions` | `extra` |
| `payment_attempts` | `request_payload`, `response_payload` |
| `video_views` | `watched_times` |

### Performance B-tree indexes

Added on frequently filtered columns: `users.last_seen`, `payments.paid_at`, `messages.created_at`, `views.created_at`, `comments.approved`, `course_user.completed_at`, `user_logins.logged_in_at`.

---

## Backward Compatibility with MySQL

Set `DB_CONNECTION=mysql` to run against MySQL during a gradual cutover. `SqlDialect` and `MigrationColumnHelpers` detect the active driver and emit the correct SQL for each platform.

---

## Troubleshooting

| Error | Fix |
|-------|-----|
| `could not find driver` | Enable `pdo_pgsql` in `php.ini` |
| `permission denied for schema public` | `GRANT CREATE ON SCHEMA public TO zanburak;` |
| `column must appear in GROUP BY` | Should not occur — all analytics queries group by alias or full expression |
| `function hour(timestamp) does not exist` | Deploy latest code with `SqlDialect` |
| `operator does not exist: citext = varchar` | Run `2026_06_13_000001_postgresql_optimizations` migration |
| Sequence out of sync after pgloader | `php artisan tinker` → `DB::statement("SELECT setval('users_id_seq', (SELECT MAX(id) FROM users))");` for each table |

---

## Rollback to MySQL

1. Set `DB_CONNECTION=mysql` in `.env`
2. Point `DB_HOST`/`DB_PORT` back to MySQL
3. `php artisan config:clear`
4. No code rollback needed — `SqlDialect` supports both drivers
