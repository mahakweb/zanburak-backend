<?php

namespace App\Listeners\Settlement;

use App\Events\Settlement\SettlementChanged;
use App\Events\Settlement\SettlementChangedBroadcast;
use Illuminate\Support\Facades\Log;

class BroadcastSettlementChanged
{
    public function handle(SettlementChanged $event): void
    {
        if (config('broadcasting.default') === 'null') {
            return;
        }

        try {
            broadcast(new SettlementChangedBroadcast(
                $event->settlementId,
                $event->teacherId,
                $event->status,
            ));
        } catch (\Throwable $e) {
            Log::warning('Settlement broadcast failed', [
                'settlement_id' => $event->settlementId,
                'error' => $e->getMessage(),
            ]);
        }
    }
};
