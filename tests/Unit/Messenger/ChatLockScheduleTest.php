<?php

namespace Tests\Unit\Messenger;

use App\Models\Conversation;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ChatLockScheduleTest extends TestCase
{
    public function test_overnight_schedule_locks_inside_window(): void
    {
        $c = new Conversation([
            'type' => Conversation::TYPE_GROUP,
            'lock_schedule' => [
                'enabled' => true,
                'start' => '22:00',
                'end' => '08:00',
                'timezone' => 'Asia/Tehran',
            ],
        ]);

        $this->assertTrue($c->isChatLockedNow(Carbon::parse('2026-07-26 23:30:00', 'Asia/Tehran')));
        $this->assertTrue($c->isChatLockedNow(Carbon::parse('2026-07-26 01:00:00', 'Asia/Tehran')));
        $this->assertFalse($c->isChatLockedNow(Carbon::parse('2026-07-26 08:00:00', 'Asia/Tehran')));
        $this->assertFalse($c->isChatLockedNow(Carbon::parse('2026-07-26 11:46:00', 'Asia/Tehran')));
        $this->assertTrue($c->isChatLockedNow(Carbon::parse('2026-07-26 07:59:00', 'Asia/Tehran')));
    }

    public function test_daytime_schedule_and_unpadded_times(): void
    {
        $c = new Conversation([
            'type' => Conversation::TYPE_GROUP,
            'lock_schedule' => [
                'enabled' => true,
                'start' => '9:0',
                'end' => '17:00',
                'timezone' => 'Asia/Tehran',
            ],
        ]);

        $this->assertTrue($c->isChatLockedNow(Carbon::parse('2026-07-26 09:00:00', 'Asia/Tehran')));
        $this->assertTrue($c->isChatLockedNow(Carbon::parse('2026-07-26 12:00:00', 'Asia/Tehran')));
        $this->assertFalse($c->isChatLockedNow(Carbon::parse('2026-07-26 17:00:00', 'Asia/Tehran')));
        $this->assertFalse($c->isChatLockedNow(Carbon::parse('2026-07-26 08:59:00', 'Asia/Tehran')));
    }

    public function test_disabled_schedule_does_not_lock(): void
    {
        $c = new Conversation([
            'type' => Conversation::TYPE_GROUP,
            'lock_schedule' => [
                'enabled' => false,
                'start' => '00:00',
                'end' => '23:59',
                'timezone' => 'Asia/Tehran',
            ],
        ]);

        $this->assertFalse($c->isChatLockedNow(Carbon::parse('2026-07-26 12:00:00', 'Asia/Tehran')));
    }

    public function test_manual_lock_overrides_schedule(): void
    {
        $c = new Conversation([
            'type' => Conversation::TYPE_GROUP,
            'messages_locked' => true,
            'lock_schedule' => [
                'enabled' => true,
                'start' => '22:00',
                'end' => '08:00',
                'timezone' => 'Asia/Tehran',
            ],
        ]);

        $this->assertTrue($c->isChatLockedNow(Carbon::parse('2026-07-26 12:00:00', 'Asia/Tehran')));
    }
}
