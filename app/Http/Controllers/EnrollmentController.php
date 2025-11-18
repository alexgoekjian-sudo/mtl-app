<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use App\Models\Enrollment;
use Illuminate\Http\Request;

class EnrollmentController extends BaseController
{
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 15);
        
        $query = Enrollment::with(['student', 'courseOffering']);
        
        if ($request->has('student_id')) {
            $query->where('student_id', $request->input('student_id'));
        }
        
        if ($request->has('course_offering_id')) {
            $query->where('course_offering_id', $request->input('course_offering_id'));
        }
        
        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }
        
        $enrollments = $query->orderBy('enrolled_at', 'desc')->paginate($perPage);
        return response()->json($enrollments);
    }

    public function show($id)
    {
        $enrollment = Enrollment::with(['student', 'courseOffering'])->findOrFail($id);
        return response()->json($enrollment);
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'student_id' => 'required|exists:students,id',
            'course_offering_id' => 'required|exists:course_offerings,id',
            'status' => 'sometimes|in:registered,active,cancelled,completed',
            'enrolled_at' => 'nullable|date',
            'is_trial' => 'sometimes|boolean',
        ]);

        $data = $request->all();
        if (!isset($data['enrolled_at'])) {
            $data['enrolled_at'] = now();
        }

        $enrollment = Enrollment::create($data);
        return response()->json($enrollment->load(['student', 'courseOffering']), 201);
    }

    public function update(Request $request, $id)
    {
        $enrollment = Enrollment::findOrFail($id);

        $this->validate($request, [
            'status' => 'sometimes|in:registered,active,cancelled,completed',
            'dropped_at' => 'nullable|date',
            'mid_course_level' => 'nullable|string|max:255',
            'mid_course_notes' => 'nullable|string',
            'is_trial' => 'sometimes|boolean',
        ]);

        $enrollment->update($request->all());
        return response()->json($enrollment->load(['student', 'courseOffering']));
    }

    public function destroy($id)
    {
        $enrollment = Enrollment::findOrFail($id);
        $enrollment->delete();
        return response()->json(['status' => 'deleted']);
    }
}
