<?php

namespace Tests\Unit\Messenger;

use App\Http\Controllers\Api\Messenger\MessengerController;
use App\Models\MessengerEvent;
use App\Services\Messenger\MessengerService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Mockery;
use Tests\TestCase;

class MessengerSyncPaginationTest extends TestCase
{
    public function test_sync_cursor_is_last_returned_event_instead_of_high_watermark(): void
    {
        $user = (object) ['id' => 42];
        $events = new Collection(collect(range(101, 151))->map(function (int $id) {
            $event = new MessengerEvent([
                'type' => 'message.new',
                'payload' => ['sequence' => $id],
            ]);
            $event->id = $id;

            return $event;
        })->all());

        $service = Mockery::mock(MessengerService::class);
        $service->shouldReceive('getLatestCursor')->once()->with(42)->andReturn(999);
        $service->shouldReceive('getEventsSince')
            ->once()
            ->with(42, 100, 51, 999)
            ->andReturn($events);

        $request = Request::create('/api/messenger/sync', 'GET', ['since' => '100']);
        $request->setUserResolver(fn () => $user);

        $response = (new MessengerController($service))->sync($request);
        $data = $response->getData(true);

        $this->assertCount(50, $data['events']);
        $this->assertSame('150', $data['cursor']);
        $this->assertTrue($data['has_more']);
        $this->assertSame('999', $data['high_watermark']);
        $this->assertSame('101', $data['events'][0]['id']);
        $this->assertSame('150', $data['events'][49]['id']);
    }

    public function test_empty_page_preserves_string_cursor(): void
    {
        $user = (object) ['id' => 42];
        $service = Mockery::mock(MessengerService::class);
        $service->shouldReceive('getLatestCursor')->once()->with(42)->andReturn(500);
        $service->shouldReceive('getEventsSince')
            ->once()
            ->with(42, 500, 51, 500)
            ->andReturn(new Collection);

        $request = Request::create('/api/messenger/sync', 'GET', ['since' => '500']);
        $request->setUserResolver(fn () => $user);

        $data = (new MessengerController($service))->sync($request)->getData(true);

        $this->assertSame([], $data['events']);
        $this->assertSame('500', $data['cursor']);
        $this->assertFalse($data['has_more']);
        $this->assertSame('500', $data['high_watermark']);
    }
}
