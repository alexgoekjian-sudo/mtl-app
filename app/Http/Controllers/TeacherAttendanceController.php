<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use App\Models\CourseOffering;
use App\Models\Session;
use App\Models\Student;
use App\Models\Enrollment;
use App\Models\AttendanceRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

/**
 * Teacher-facing API for attendance management
 * Simpler endpoints focused on daily attendance tracking
 */
class TeacherAttendanceController extends BaseController
{
    /**
     * Get all courses (for teachers to select)
     */
    public function getCourses()
    {
        $courses = CourseOffering::select('id', 'attendance_id', 'course_full_name', 'level', 'program', 'type', 'start_date', 'end_date')
            ->where('end_date', '>=', Carbon::now())
            ->orderBy('start_date', 'desc')
            ->get();
        
        return response()->json($courses);
    }
    
    /**
     * Get single course details
     */
    public function getCourse($id)
    {
        $course = CourseOffering::findOrFail($id);
        return response()->json($course);
    }
    
    /**
     * Get attendance for a specific date
     * Returns: session, students enrolled, attendance records
     */
    public function getAttendance($id, Request $request)
    {
        $date = $request->get('date', Carbon::now()->format('Y-m-d'));
        
        // Find or get session for this date
        $session = Session::where('course_offering_id', $id)
            ->where('date', $date)
            ->first();
        
        // Get enrolled students with enrollment data
        $students = Student::select(
                'students.*',
                'enrollments.mid_course_level',
                'enrollments.mid_course_notes'
            )
            ->join('enrollments', 'enrollments.student_id', '=', 'students.id')
            ->where('enrollments.course_offering_id', $id)
            ->where('enrollments.status', 'active')
            ->orderBy('students.last_name')
            ->orderBy('students.first_name')
            ->get()
            ->map(function($student) {
                // Add aliases for backward compatibility with frontend
                $student->country = $student->country_of_origin;
                $student->notes = $student->mid_course_notes;
                // If mid_course_level exists, use it as current_level, otherwise use student's current_level
                if ($student->mid_course_level) {
                    $student->current_level = $student->mid_course_level;
                }
                return $student;
            });
        
        // Get attendance records if session exists
        $attendance = [];
        if ($session) {
            $attendance = AttendanceRecord::where('session_id', $session->id)
                ->get();
        }
        
        return response()->json([
            'session' => $session,
            'students' => $students,
            'attendance' => $attendance,
            'date' => $date
        ]);
    }
    
    /**
     * Create a new session
     */
    public function createSession($id, Request $request)
    {
        $this->validate($request, [
            'session_date' => 'required|date'
        ]);
        
        // Check if session already exists
        $existing = Session::where('course_offering_id', $id)
            ->where('date', $request->session_date)
            ->first();
        
        if ($existing) {
            return response()->json($existing);
        }
        
        $session = Session::create([
            'course_offering_id' => $id,
            'date' => $request->session_date
        ]);
        
        return response()->json($session, 201);
    }
    
    /**
     * Save attendance for multiple students
     * Body: { session_date, attendance: { student_id: { status, note } } }
     */
    public function saveAttendance($id, Request $request)
    {
        $this->validate($request, [
            'session_date' => 'required|date',
            'attendance' => 'required|array'
        ]);
        
        // Find or create session
        $session = Session::firstOrCreate(
            [
                'course_offering_id' => $id,
                'date' => $request->session_date
            ]
        );
        
        // Save attendance records
        $created = 0;
        $updated = 0;
        
        foreach ($request->attendance as $studentId => $data) {
            $status = $data['status'] ?? 'present';
            $note = $data['note'] ?? null;
            
            // Skip if status is 'none'
            if ($status === 'none') {
                // Delete if exists
                AttendanceRecord::where('session_id', $session->id)
                    ->where('student_id', $studentId)
                    ->delete();
                continue;
            }
            
            $record = AttendanceRecord::updateOrCreate(
                [
                    'session_id' => $session->id,
                    'student_id' => $studentId
                ],
                [
                    'status' => $status,
                    'note' => $note,
                    'recorded_at' => now()
                ]
            );
            
            if ($record->wasRecentlyCreated) {
                $created++;
            } else {
                $updated++;
            }
        }
        
        return response()->json([
            'session' => $session,
            'created' => $created,
            'updated' => $updated
        ]);
    }
    
    /**
     * Get attendance summary for a course
     * Shows overall attendance statistics
     */
    public function getCourseSummary($id)
    {
        $course = CourseOffering::findOrFail($id);
        
        // Get all sessions
        $sessions = Session::where('course_offering_id', $id)
            ->orderBy('date')
            ->get();
        
        // Get enrolled students
        $studentCount = Enrollment::where('course_offering_id', $id)
            ->where('status', 'active')
            ->count();
        
        // Get attendance statistics
        $totalRecords = AttendanceRecord::whereIn('session_id', $sessions->pluck('id'))
            ->count();
        
        $statusCounts = AttendanceRecord::whereIn('session_id', $sessions->pluck('id'))
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status');
        
        // Calculate attendance rate
        $presentAndLate = ($statusCounts['present'] ?? 0) + ($statusCounts['late'] ?? 0);
        $expectedTotal = $sessions->count() * $studentCount;
        $attendanceRate = $expectedTotal > 0 ? round(($presentAndLate / $expectedTotal) * 100, 1) : 0;
        
        return response()->json([
            'course' => $course,
            'total_sessions' => $sessions->count(),
            'enrolled_students' => $studentCount,
            'attendance_records' => $totalRecords,
            'status_breakdown' => $statusCounts,
            'attendance_rate' => $attendanceRate . '%',
            'sessions' => $sessions
        ]);
    }
    
    /**
     * Get student attendance history for a course
     */
    public function getStudentAttendance($courseId, $studentId)
    {
        $sessions = Session::where('course_offering_id', $courseId)
            ->orderBy('date')
            ->get();
        
        $attendance = AttendanceRecord::whereIn('session_id', $sessions->pluck('id'))
            ->where('student_id', $studentId)
            ->get()
            ->keyBy('session_id');
        
        $history = $sessions->map(function($session) use ($attendance) {
            $record = $attendance->get($session->id);
            // Get raw date value from database (Y-m-d format)
            $dateOnly = $session->getAttributes()['date'];
            return [
                'date' => $dateOnly,
                'status' => $record ? $record->status : 'not_recorded',
                'note' => $record ? $record->note : null
            ];
        });
        
        // Calculate stats
        $present = $history->where('status', 'present')->count();
        $late = $history->where('status', 'late')->count();
        $absent = $history->where('status', 'absent')->count();
        $total = $sessions->count();
        $rate = $total > 0 ? round((($present + $late) / $total) * 100, 1) : 0;
        
        return response()->json([
            'history' => $history,
            'statistics' => [
                'present' => $present,
                'late' => $late,
                'absent' => $absent,
                'total_sessions' => $total,
                'attendance_rate' => $rate . '%'
            ]
        ]);
    }
}
