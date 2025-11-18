<?php

namespace App\Http\Controllers;

use App\Models\Session;
use Illuminate\Http\Request;

class SessionController extends Controller
{
    /**
     * List sessions with pagination
     */
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 15);
        
        $query = Session::with(['courseOffering', 'teacher']);
        
        // Optional filter by course offering
        if ($request->has('course_offering_id')) {
            $query->where('course_offering_id', $request->input('course_offering_id'));
        }
        
        $sessions = $query->orderBy('date', 'desc')
                          ->orderBy('start_time', 'asc')
                          ->paginate($perPage);
        
        return response()->json($sessions);
    }

    /**
     * Show a single session
     */
    public function show($id)
    {
        $session = Session::with(['courseOffering', 'teacher'])->findOrFail($id);
        return response()->json($session);
    }

    /**
     * Create a new session
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'course_offering_id' => 'required|exists:course_offerings,id',
            'date' => 'required|date',
            'start_time' => 'required',
            'end_time' => 'required',
            'teacher_id' => 'nullable|exists:users,id',
        ]);

        $session = Session::create($request->all());
        
        return response()->json($session->load(['courseOffering', 'teacher']), 201);
    }

    /**
     * Update an existing session
     */
    public function update(Request $request, $id)
    {
        $session = Session::findOrFail($id);

        $this->validate($request, [
            'course_offering_id' => 'sometimes|exists:course_offerings,id',
            'date' => 'sometimes|date',
            'start_time' => 'sometimes',
            'end_time' => 'sometimes',
            'teacher_id' => 'nullable|exists:users,id',
        ]);

        $session->update($request->all());
        
        return response()->json($session->load(['courseOffering', 'teacher']));
    }

    /**
     * Delete a session
     */
    public function destroy($id)
    {
        $session = Session::findOrFail($id);
        $session->delete();
        
        return response()->json(['status' => 'deleted']);
    }
}
