<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use App\Models\Task;
use Illuminate\Http\Request;

class TaskController extends BaseController
{
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 15);
        
        $query = Task::with(['assignedTo', 'createdBy']);
        
        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }
        
        if ($request->has('assigned_to_user_id')) {
            $query->where('assigned_to_user_id', $request->input('assigned_to_user_id'));
        }
        
        if ($request->has('priority')) {
            $query->where('priority', $request->input('priority'));
        }
        
        $tasks = $query->orderBy('due_at', 'asc')->paginate($perPage);
        return response()->json($tasks);
    }

    public function show($id)
    {
        $task = Task::with(['assignedTo', 'createdBy'])->findOrFail($id);
        return response()->json($task);
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'title' => 'required|string|max:255',
            'body' => 'nullable|string',
            'assigned_to_user_id' => 'nullable|exists:users,id',
            'related_entity_type' => 'nullable|string|max:255',
            'related_entity_id' => 'nullable|integer',
            'due_at' => 'nullable|date',
            'status' => 'sometimes|in:pending,completed,cancelled',
            'priority' => 'sometimes|in:low,medium,high',
            'created_by_user_id' => 'nullable|exists:users,id',
        ]);

        $task = Task::create($request->all());
        return response()->json($task->load(['assignedTo', 'createdBy']), 201);
    }

    public function update(Request $request, $id)
    {
        $task = Task::findOrFail($id);

        $this->validate($request, [
            'title' => 'sometimes|string|max:255',
            'body' => 'nullable|string',
            'assigned_to_user_id' => 'nullable|exists:users,id',
            'due_at' => 'nullable|date',
            'status' => 'sometimes|in:pending,completed,cancelled',
            'priority' => 'sometimes|in:low,medium,high',
        ]);

        $task->update($request->all());
        return response()->json($task->load(['assignedTo', 'createdBy']));
    }

    public function destroy($id)
    {
        $task = Task::findOrFail($id);
        $task->delete();
        return response()->json(['status' => 'deleted']);
    }

    public function complete($id)
    {
        $task = Task::findOrFail($id);
        $task->markCompleted();
        return response()->json($task);
    }
}
