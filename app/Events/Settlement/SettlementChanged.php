<?php

namespace App\Events\Settlement;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SettlementChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public int $settlementId,
        public ?int $teacherId,
        public string $status,
        public ?int $actorId = null,
    ) {
    }
};
