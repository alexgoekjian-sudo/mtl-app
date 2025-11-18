<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use App\Models\WebhookEvent;
use Illuminate\Http\Request;

class WebhookEventController extends BaseController
{
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 15);
        
        $query = WebhookEvent::query();
        
        if ($request->has('provider')) {
            $query->where('provider', $request->input('provider'));
        }
        
        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }
        
        $events = $query->orderBy('created_at', 'desc')->paginate($perPage);
        return response()->json($events);
    }

    public function show($id)
    {
        $event = WebhookEvent::findOrFail($id);
        return response()->json($event);
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'provider' => 'required|string|max:255',
            'event_type' => 'required|string|max:255',
            'external_id' => 'required|string|max:255',
            'payload' => 'required|array',
        ]);

        // Check for duplicate external_id to prevent duplicate processing
        $existing = WebhookEvent::where('provider', $request->input('provider'))
            ->where('external_id', $request->input('external_id'))
            ->first();
            
        if ($existing) {
            return response()->json([
                'message' => 'Webhook already processed',
                'event' => $existing
            ], 200);
        }

        $event = WebhookEvent::create($request->all());
        return response()->json($event, 201);
    }

    public function update(Request $request, $id)
    {
        $event = WebhookEvent::findOrFail($id);

        $this->validate($request, [
            'status' => 'sometimes|in:pending,processed,failed',
            'error_message' => 'nullable|string',
        ]);

        $event->update($request->all());
        return response()->json($event);
    }

    public function destroy($id)
    {
        $event = WebhookEvent::findOrFail($id);
        $event->delete();
        return response()->json(['status' => 'deleted']);
    }

    public function retry($id)
    {
        $event = WebhookEvent::findOrFail($id);
        
        if ($event->status !== 'failed') {
            return response()->json(['message' => 'Can only retry failed events'], 400);
        }
        
        $event->update([
            'status' => 'pending',
            'retry_count' => $event->retry_count + 1,
            'last_retry_at' => now(),
            'error_message' => null,
        ]);
        
        return response()->json($event);
    }
}
