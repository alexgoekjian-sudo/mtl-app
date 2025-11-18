<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\CourseOffering;
use App\Models\Session;
use App\Models\Lead;
use App\Models\Enrollment;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Booking;
use App\Models\Task;
use App\Models\DiscountRule;
use App\Models\CertificateExport;
use App\Models\AttendanceRecord;
use App\Models\WebhookEvent;
use App\Models\EmailLog;
use App\Models\AuditLog;

class RetoolController extends BaseController
{
    /**
     * Return students as a simple array (no pagination wrapper) for Retool
     */
    public function students(Request $request)
    {
        $limit = (int) $request->get('limit', 1000);
        $students = Student::limit($limit)->get();
        return response()->json($students);
    }

    /**
     * Return course_offerings as a simple array for Retool
     */
    public function courseOfferings(Request $request)
    {
        $limit = (int) $request->get('limit', 1000);
        $courses = CourseOffering::limit($limit)->get();
        return response()->json($courses);
    }

    /**
     * Return sessions as a simple array for Retool
     */
    public function sessions(Request $request)
    {
        $limit = (int) $request->get('limit', 1000);
        $sessions = Session::with(['courseOffering', 'teacher'])
            ->orderBy('date', 'desc')
            ->orderBy('start_time', 'asc')
            ->limit($limit)
            ->get();
        return response()->json($sessions);
    }

    /**
     * Return all data Retool might need in one call (optional convenience)
     */
    public function all(Request $request)
    {
        $limit = (int) $request->get('limit', 500);
        return response()->json([
            'students' => Student::limit($limit)->get(),
            'course_offerings' => CourseOffering::limit($limit)->get(),
            'sessions' => Session::with(['courseOffering', 'teacher'])->limit($limit)->get(),
            'leads' => Lead::limit($limit)->orderBy('created_at', 'desc')->get(),
            'enrollments' => Enrollment::with(['student', 'courseOffering'])->limit($limit)->get(),
            'invoices' => Invoice::with(['student', 'payments'])->limit($limit)->orderBy('issued_date', 'desc')->get(),
            'payments' => Payment::with('invoice')->limit($limit)->orderBy('recorded_at', 'desc')->get(),
            'bookings' => Booking::with(['lead', 'student', 'assignedTeacher'])->limit($limit)->orderBy('scheduled_at', 'desc')->get(),
            'tasks' => Task::with(['assignedTo', 'createdBy'])->limit($limit)->orderBy('due_at', 'asc')->get(),
            'discount_rules' => DiscountRule::all(),
            'certificate_exports' => CertificateExport::with(['student', 'courseOffering'])->limit($limit)->get(),
            'attendance_records' => AttendanceRecord::with(['session', 'student'])->limit($limit)->get(),
        ]);
    }

    /**
     * Return leads as a simple array for Retool
     */
    public function leads(Request $request)
    {
        $limit = (int) $request->get('limit', 1000);
        $leads = Lead::with('bookings')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
        return response()->json($leads);
    }

    /**
     * Return enrollments as a simple array for Retool
     */
    public function enrollments(Request $request)
    {
        $limit = (int) $request->get('limit', 1000);
        $enrollments = Enrollment::with(['student', 'courseOffering'])
            ->orderBy('enrolled_at', 'desc')
            ->limit($limit)
            ->get();
        return response()->json($enrollments);
    }

    /**
     * Return invoices as a simple array for Retool
     */
    public function invoices(Request $request)
    {
        $limit = (int) $request->get('limit', 1000);
        $invoices = Invoice::with(['student', 'payments'])
            ->orderBy('issued_date', 'desc')
            ->limit($limit)
            ->get();
        return response()->json($invoices);
    }

    /**
     * Return payments as a simple array for Retool
     */
    public function payments(Request $request)
    {
        $limit = (int) $request->get('limit', 1000);
        $payments = Payment::with('invoice')
            ->orderBy('recorded_at', 'desc')
            ->limit($limit)
            ->get();
        return response()->json($payments);
    }

    /**
     * Return bookings as a simple array for Retool
     */
    public function bookings(Request $request)
    {
        $limit = (int) $request->get('limit', 1000);
        $bookings = Booking::with(['lead', 'student', 'assignedTeacher'])
            ->orderBy('scheduled_at', 'desc')
            ->limit($limit)
            ->get();
        return response()->json($bookings);
    }

    /**
     * Return tasks as a simple array for Retool
     */
    public function tasks(Request $request)
    {
        $limit = (int) $request->get('limit', 1000);
        $tasks = Task::with(['assignedTo', 'createdBy'])
            ->orderBy('due_at', 'asc')
            ->limit($limit)
            ->get();
        return response()->json($tasks);
    }

    /**
     * Return discount rules as a simple array for Retool
     */
    public function discountRules(Request $request)
    {
        // All discount rules (no limit needed, typically small dataset)
        $rules = DiscountRule::orderBy('name')->get();
        return response()->json($rules);
    }

    /**
     * Return certificate exports as a simple array for Retool
     */
    public function certificateExports(Request $request)
    {
        $limit = (int) $request->get('limit', 1000);
        $exports = CertificateExport::with(['student', 'courseOffering'])
            ->orderBy('exported_at', 'desc')
            ->limit($limit)
            ->get();
        return response()->json($exports);
    }

    /**
     * Return attendance records as a simple array for Retool
     */
    public function attendanceRecords(Request $request)
    {
        $limit = (int) $request->get('limit', 1000);
        $records = AttendanceRecord::with(['session', 'student', 'recordedBy'])
            ->orderBy('recorded_at', 'desc')
            ->limit($limit)
            ->get();
        return response()->json($records);
    }

    /**
     * Return webhook events as a simple array for Retool
     */
    public function webhookEvents(Request $request)
    {
        $limit = (int) $request->get('limit', 1000);
        $events = WebhookEvent::orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
        return response()->json($events);
    }

    /**
     * Return email logs as a simple array for Retool
     */
    public function emailLogs(Request $request)
    {
        $limit = (int) $request->get('limit', 1000);
        $logs = EmailLog::with('sentBy')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
        return response()->json($logs);
    }

    /**
     * Return audit logs as a simple array for Retool
     */
    public function auditLogs(Request $request)
    {
        $limit = (int) $request->get('limit', 1000);
        $logs = AuditLog::with(['user', 'auditable'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
        return response()->json($logs);
    }
}
