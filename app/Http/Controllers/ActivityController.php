<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use App\Models\Activity;
use Illuminate\Http\Request;

class ActivityController extends BaseController
{
    /**
     * Get activities for a specific entity (lead, student, or enrollment)
     */
    public function index(Request $request)
    {
        $this->validate($request, [
            'entity_type' => 'required|in:Lead,Student,Enrollment',
            'entity_id' => 'required|integer',
            'type' => 'sometimes|in:note,call,email,meeting,level_check,payment,enrollment,other'
        ]);

        $query = Activity::where('related_entity_type', 'App\\Models\\' . $request->input('entity_type'))
            ->where('related_entity_id', $request->input('entity_id'));

        if ($request->has('type')) {
            $query->where('activity_type', $request->input('type'));
        }

        $activities = $query->orderBy('created_at', 'desc')->get();
        return response()->json($activities);
    }

    /**
     * Create a new activity
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'entity_type' => 'required|in:Lead,Student,Enrollment',
            'entity_id' => 'required|integer',
            'activity_type' => 'required|in:note,call,email,meeting,level_check,payment,enrollment,other',
            'subject' => 'nullable|string|max:255',
            'body' => 'required|string',
            'created_by_user_id' => 'nullable|exists:users,id'
        ]);

        $activity = Activity::create([
            'related_entity_type' => 'App\\Models\\' . $request->input('entity_type'),
            'related_entity_id' => $request->input('entity_id'),
            'activity_type' => $request->input('activity_type'),
            'subject' => $request->input('subject'),
            'body' => $request->input('body'),
            'created_by_user_id' => $request->input('created_by_user_id')
        ]);

        return response()->json($activity, 201);
    }

    /**
     * Update an activity
     */
    public function update(Request $request, $id)
    {
        $activity = Activity::findOrFail($id);

        $this->validate($request, [
            'subject' => 'sometimes|string|max:255',
            'body' => 'sometimes|string'
        ]);

        $activity->update($request->only(['subject', 'body']));
        return response()->json($activity);
    }

    /**
     * Delete an activity
     */
    public function destroy($id)
    {
        $activity = Activity::findOrFail($id);
        $activity->delete();
        return response()->json(['status' => 'deleted']);
    }
}
