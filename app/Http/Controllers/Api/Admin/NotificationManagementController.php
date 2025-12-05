<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class NotificationManagementController extends Controller
{
    // Event Groups Management
    public function eventGroups(Request $request)
    {
        $perPage = $request->input('perPage', 10);
        $search = $request->input('search');

        $query = EventGroup::with(['events']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('english_title', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        $eventGroups = $query->orderBy('created_at', 'desc')->paginate($perPage);

        $data = $eventGroups->map(function ($group) {
            return [
                'id' => $group->id,
                'title' => $group->title,
                'english_title' => $group->english_title,
                'slug' => $group->slug,
                'icon' => $group->icon,
                'description' => $group->description,
                'events_count' => $group->events->count(),
                'created_at' => $group->created_at,
                'updated_at' => $group->updated_at,
            ];
        });

        return response()->json([
            'message' => 'Success',
            'event_groups' => $data,
            'pagination' => [
                'total' => $eventGroups->total(),
                'per_page' => $eventGroups->perPage(),
                'current_page' => $eventGroups->currentPage(),
                'last_page' => $eventGroups->lastPage(),
                'prev_page' => $eventGroups->currentPage() > 1 ? $eventGroups->currentPage() - 1 : null,
                'next_page' => $eventGroups->hasMorePages() ? $eventGroups->currentPage() + 1 : null
            ]
        ]);
    }

    public function createEventGroup(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => ['required', 'string', 'max:255'],
            'english_title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'icon' => ['nullable', 'string'],
        ]);

        if (!$validator->passes()) {
            return response()->json(['message' => 'Error', 'errors' => $validator->errors()->toArray()], 422);
        }

        $validatedData = $validator->validated();
        $eventGroup = EventGroup::create($validatedData);

        return response()->json([
            'message' => 'Event group created successfully',
            'event_group' => $eventGroup
        ], 201);
    }

    public function updateEventGroup(Request $request, EventGroup $eventGroup)
    {
        $validator = Validator::make($request->all(), [
            'title' => ['required', 'string', 'max:255'],
            'english_title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'icon' => ['nullable', 'string'],
        ]);

        if (!$validator->passes()) {
            return response()->json(['message' => 'Error', 'errors' => $validator->errors()->toArray()], 422);
        }

        $validatedData = $validator->validated();
        $eventGroup->update($validatedData);

        return response()->json([
            'message' => 'Event group updated successfully',
            'event_group' => $eventGroup
        ]);
    }

    public function deleteEventGroup(EventGroup $eventGroup)
    {
        $eventGroup->delete();

        return response()->json([
            'message' => 'Event group deleted successfully'
        ]);
    }

    public function getEventGroup(EventGroup $eventGroup)
    {
        return response()->json([
            'message' => 'Success',
            'event_group' => [
                'id' => $eventGroup->id,
                'title' => $eventGroup->title,
                'english_title' => $eventGroup->english_title,
                'slug' => $eventGroup->slug,
                'icon' => $eventGroup->icon,
                'description' => $eventGroup->description,
                'created_at' => $eventGroup->created_at,
                'updated_at' => $eventGroup->updated_at,
            ]
        ]);
    }

    // Events Management
    public function events(Request $request)
    {
        $perPage = $request->input('perPage', 10);
        $search = $request->input('search');
        $eventGroupId = $request->input('event_group_id');

        $query = Event::with(['eventGroup']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('english_title', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        if ($eventGroupId) {
            $query->where('event_group_id', $eventGroupId);
        }

        $events = $query->orderBy('created_at', 'desc')->paginate($perPage);

        $data = $events->map(function ($event) {
            return [
                'id' => $event->id,
                'title' => $event->title,
                'english_title' => $event->english_title,
                'slug' => $event->slug,
                'icon' => $event->icon,
                'description' => $event->description,
                'event_group_id' => $event->event_group_id,
                'event_group' => $event->eventGroup ? [
                    'id' => $event->eventGroup->id,
                    'title' => $event->eventGroup->title,
                    'slug' => $event->eventGroup->slug,
                ] : null,
                'is_email_enabled' => $event->is_email_enabled,
                'is_sms_enabled' => $event->is_sms_enabled,
                'is_telegram_enabled' => $event->is_telegram_enabled,
                'is_site_enabled' => $event->is_site_enabled,
                'created_at' => $event->created_at,
                'updated_at' => $event->updated_at,
            ];
        });

        return response()->json([
            'message' => 'Success',
            'events' => $data,
            'pagination' => [
                'total' => $events->total(),
                'per_page' => $events->perPage(),
                'current_page' => $events->currentPage(),
                'last_page' => $events->lastPage(),
                'prev_page' => $events->currentPage() > 1 ? $events->currentPage() - 1 : null,
                'next_page' => $events->hasMorePages() ? $events->currentPage() + 1 : null
            ]
        ]);
    }

    public function createEvent(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => ['required', 'string', 'max:255'],
            'english_title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'icon' => ['nullable', 'string'],
            'event_group_id' => ['required', 'exists:event_groups,id'],
            'is_email_enabled' => ['boolean'],
            'is_sms_enabled' => ['boolean'],
            'is_telegram_enabled' => ['boolean'],
            'is_site_enabled' => ['boolean'],
        ]);

        if (!$validator->passes()) {
            return response()->json(['message' => 'Error', 'errors' => $validator->errors()->toArray()], 422);
        }

        $validatedData = $validator->validated();
        
        // Set default values for channel enables if not provided
        $validatedData['is_email_enabled'] = $validatedData['is_email_enabled'] ?? true;
        $validatedData['is_sms_enabled'] = $validatedData['is_sms_enabled'] ?? true;
        $validatedData['is_telegram_enabled'] = $validatedData['is_telegram_enabled'] ?? true;
        $validatedData['is_site_enabled'] = $validatedData['is_site_enabled'] ?? true;

        $event = Event::create($validatedData);

        return response()->json([
            'message' => 'Event created successfully',
            'event' => $event
        ], 201);
    }

    public function updateEvent(Request $request, Event $event)
    {
        $validator = Validator::make($request->all(), [
            'title' => ['required', 'string', 'max:255'],
            'english_title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'icon' => ['nullable', 'string'],
            'event_group_id' => ['required', 'exists:event_groups,id'],
            'is_email_enabled' => ['boolean'],
            'is_sms_enabled' => ['boolean'],
            'is_telegram_enabled' => ['boolean'],
            'is_site_enabled' => ['boolean'],
        ]);

        if (!$validator->passes()) {
            return response()->json(['message' => 'Error', 'errors' => $validator->errors()->toArray()], 422);
        }

        $validatedData = $validator->validated();
        $event->update($validatedData);

        return response()->json([
            'message' => 'Event updated successfully',
            'event' => $event
        ]);
    }

    public function deleteEvent(Event $event)
    {
        $event->delete();

        return response()->json([
            'message' => 'Event deleted successfully'
        ]);
    }

    public function getEvent(Event $event)
    {
        return response()->json([
            'message' => 'Success',
            'event' => [
                'id' => $event->id,
                'title' => $event->title,
                'english_title' => $event->english_title,
                'slug' => $event->slug,
                'icon' => $event->icon,
                'description' => $event->description,
                'event_group_id' => $event->event_group_id,
                'event_group' => $event->eventGroup ? [
                    'id' => $event->eventGroup->id,
                    'title' => $event->eventGroup->title,
                    'slug' => $event->eventGroup->slug,
                ] : null,
                'is_email_enabled' => $event->is_email_enabled,
                'is_sms_enabled' => $event->is_sms_enabled,
                'is_telegram_enabled' => $event->is_telegram_enabled,
                'is_site_enabled' => $event->is_site_enabled,
                'created_at' => $event->created_at,
                'updated_at' => $event->updated_at,
            ]
        ]);
    }

    // Get all event groups for dropdowns
    public function getAllEventGroups()
    {
        $eventGroups = EventGroup::select('id', 'title', 'english_title', 'slug')->get();

        return response()->json([
            'message' => 'Success',
            'event_groups' => $eventGroups
        ]);
    }
}

