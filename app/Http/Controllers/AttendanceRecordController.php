<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use App\Models\AttendanceRecord;
use Illuminate\Http\Request;

class AttendanceRecordController extends BaseController
{
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 15);
        
        $query = AttendanceRecord::with(['session', 'student', 'recordedBy']);
        
        if ($request->has('session_id')) {
            $query->where('session_id', $request->input('session_id'));
        }
        
        if ($request->has('student_id')) {
            $query->where('student_id', $request->input('student_id'));
        }
        
        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }
        
        $records = $query->orderBy('recorded_at', 'desc')->paginate($perPage);
        return response()->json($records);
    }

    public function show($id)
    {
        $record = AttendanceRecord::with(['session', 'student', 'recordedBy'])->findOrFail($id);
        return response()->json($record);
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'session_id' => 'required|exists:sessions,id',
            'student_id' => 'required|exists:students,id',
            'status' => 'required|in:present,absent,late,excused',
            'note' => 'nullable|string',
            'recorded_by' => 'nullable|exists:users,id',
            'recorded_at' => 'nullable|date',
        ]);

        $data = $request->all();
        if (!isset($data['recorded_at'])) {
            $data['recorded_at'] = now();
        }

        $record = AttendanceRecord::create($data);
        return response()->json($record->load(['session', 'student', 'recordedBy']), 201);
    }

    public function update(Request $request, $id)
    {
        $record = AttendanceRecord::findOrFail($id);

        $this->validate($request, [
            'status' => 'sometimes|in:present,absent,late,excused',
            'note' => 'nullable|string',
        ]);

        $record->update($request->all());
        return response()->json($record->load(['session', 'student', 'recordedBy']));
    }

    public function destroy($id)
    {
        $record = AttendanceRecord::findOrFail($id);
        $record->delete();
        return response()->json(['status' => 'deleted']);
    }
}
