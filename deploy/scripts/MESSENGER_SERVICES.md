# Messenger runtime services (server)

On production, Redis is the **Linux package** `redis-server` (apt), not the Windows portable under `C:\zanburak\tools\redis`.

## Required processes

| Process | How | Role |
|---------|-----|------|
| `redis-server` | apt + systemd | Messenger RAM / outbox |
| `laravel-reverb` | systemd unit in this folder | Live WebSocket |
| `laravel-scheduler` | systemd (`schedule:work`) | Flush outbox every ~2s |
| `laravel-queue` | systemd | Other queued jobs |
| nginx + php-fpm | already on server | HTTP API (**not** `php artisan serve`) |

## One-time / after deploy (Deploy Manager menu)

1. Upload+extract (option 1)
2. **[20] Install/create service unit files** — installs Redis via apt if missing, uploads scheduler/reverb/queue/meilisearch units
3. **[21] Start services**
4. **[5] migrate --force**
5. **[13] Rebuild cache**

Or manually on the server:

```bash
sudo apt-get update
sudo apt-get install -y redis-server
sudo systemctl enable --now redis-server
redis-cli ping   # PONG

# From deploy menu [20]/[21], or:
sudo systemctl enable --now laravel-reverb laravel-scheduler laravel-queue
sudo systemctl status redis-server laravel-reverb laravel-scheduler
```

## Env (server `.env` / production-env.txt)

```
MESSENGER_REDIS=true
MESSENGER_HOT_PATH=true
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
BROADCAST_DRIVER=reverb
```

## Supervisor alternative

If you use Supervisor instead of systemd for app workers, copy:

- `supervisor/zanburak-reverb.conf`
- `supervisor/zanburak-scheduler.conf`

Redis should still run as the system `redis-server` service.
