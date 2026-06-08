<?php

namespace App\Http\Controllers\Api\Messenger;

use App\Http\Controllers\Controller;
use App\Services\Messenger\MessengerService;
use App\Services\Messenger\RealtimeBus;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Server-Sent Events endpoint for self-hosted realtime messenger delivery.
 * Works with or without Redis — the database event log is always the source of truth.
 */
class StreamController extends Controller
{
    public function __construct(
        protected MessengerService $messenger,
        protected RealtimeBus $bus
    ) {}

    public function stream(Request $request): StreamedResponse
    {
        $user = $request->user();
        $sinceId = $request->integer('since', 0);

        // Support Last-Event-ID header for automatic reconnect
        if ($sinceId === 0 && $request->header('Last-Event-ID')) {
            $sinceId = (int) $request->header('Last-Event-ID');
        }

        $ttl = config('messenger.stream.ttl', 30);
        $heartbeat = config('messenger.stream.heartbeat', 15);
        $pollMs = config('messenger.stream.poll_interval', 1000);
        $redisBlock = config('messenger.stream.redis_block', 5);

        return new StreamedResponse(function () use ($user, $sinceId, $ttl, $heartbeat, $pollMs, $redisBlock) {
            // Disable output buffering for immediate delivery
            if (ob_get_level()) {
                ob_end_clean();
            }

            $cursor = $sinceId;
            $startedAt = time();
            $lastHeartbeat = time();

            // Send initial connection event
            $this->sendEvent('connected', ['cursor' => $cursor, 'user_id' => $user->id], $cursor);

            while (time() - $startedAt < $ttl) {
                // Check for new events
                $events = $this->messenger->getEventsSince($user->id, $cursor, 50);

                foreach ($events as $event) {
                    $this->sendEvent($event->type, $event->payload ?? [], $event->id);
                    $cursor = $event->id;
                }

                // Heartbeat to keep connection alive
                if (time() - $lastHeartbeat >= $heartbeat) {
                    echo ": heartbeat\n\n";
                    $this->flush();
                    $lastHeartbeat = time();
                }

                // Check if client disconnected
                if (connection_aborted()) {
                    break;
                }

                // Wait for Redis wake signal or poll interval
                if ($this->bus->isAvailable()) {
                    $this->bus->waitForWake($user->id, $redisBlock);
                } else {
                    usleep($pollMs * 1000);
                }
            }

            // Tell client to reconnect
            $this->sendEvent('reconnect', ['cursor' => $cursor], $cursor);
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    protected function sendEvent(string $type, array $data, int $id): void
    {
        echo "id: {$id}\n";
        echo "event: {$type}\n";
        echo 'data: '.json_encode($data, JSON_UNESCAPED_UNICODE)."\n\n";
        $this->flush();
    }

    protected function flush(): void
    {
        if (ob_get_level()) {
            ob_flush();
        }
        flush();
    }
}
