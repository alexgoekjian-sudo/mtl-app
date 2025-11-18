<?php

$router->get('/', function () use ($router) {
    return response()->json(['ok' => true, 'app' => 'MTL_App']);
});

$router->get('/health', function () use ($router) {
    return response()->json(['status' => 'ok']);
});

$router->get('/status', function () use ($router) {
    return response()->json(['status' => 'ok', 'route' => '/status']);
});

// Minimal API endpoints for Retool to call for side-effectful operations.
// Retool endpoints (protected)
// These are protected by API token auth in the group below.

// Authentication
$router->post('/api/auth/login', 'App\Http\Controllers\AuthController@login');

// Handle OPTIONS preflight for CORS
$router->options('/{any:.*}', function () {
    return response('', 200);
});

// Protected APIs (require token)
$router->group(['middleware' => 'auth.token', 'prefix' => 'api'], function () use ($router) {
    $router->post('/auth/logout', 'App\Http\Controllers\AuthController@logout');
    $router->get('/auth/me', 'App\Http\Controllers\AuthController@me');

    // Protected Retool endpoints
    $router->post('/record-payment', 'App\Http\Controllers\RecordPaymentController@handle');
    $router->post('/trigger-import', 'App\Http\Controllers\ImportController@trigger');
    // Student and CourseOffering CRUD
    $router->get('/students', 'App\\Http\\Controllers\\StudentController@index');
    $router->post('/students', 'App\\Http\\Controllers\\StudentController@store');
    $router->get('/students/{id}', 'App\\Http\\Controllers\\StudentController@show');
    $router->put('/students/{id}', 'App\\Http\\Controllers\\StudentController@update');
    $router->delete('/students/{id}', 'App\\Http\\Controllers\\StudentController@destroy');

    $router->get('/course_offerings', 'App\\Http\\Controllers\\CourseOfferingController@index');
    $router->post('/course_offerings', 'App\\Http\\Controllers\\CourseOfferingController@store');
    $router->get('/course_offerings/{id}', 'App\\Http\\Controllers\\CourseOfferingController@show');
    $router->put('/course_offerings/{id}', 'App\\Http\\Controllers\\CourseOfferingController@update');
    $router->delete('/course_offerings/{id}', 'App\\Http\\Controllers\\CourseOfferingController@destroy');

    // Session CRUD
    $router->get('/sessions', 'App\\Http\\Controllers\\SessionController@index');
    $router->post('/sessions', 'App\\Http\\Controllers\\SessionController@store');
    $router->get('/sessions/{id}', 'App\\Http\\Controllers\\SessionController@show');
    $router->put('/sessions/{id}', 'App\\Http\\Controllers\\SessionController@update');
    $router->delete('/sessions/{id}', 'App\\Http\\Controllers\\SessionController@destroy');

    // Lead CRUD
    $router->get('/leads', 'App\\Http\\Controllers\\LeadController@index');
    $router->post('/leads', 'App\\Http\\Controllers\\LeadController@store');
    $router->get('/leads/{id}', 'App\\Http\\Controllers\\LeadController@show');
    $router->put('/leads/{id}', 'App\\Http\\Controllers\\LeadController@update');
    $router->delete('/leads/{id}', 'App\\Http\\Controllers\\LeadController@destroy');

    // Enrollment CRUD
    $router->get('/enrollments', 'App\\Http\\Controllers\\EnrollmentController@index');
    $router->post('/enrollments', 'App\\Http\\Controllers\\EnrollmentController@store');
    $router->get('/enrollments/{id}', 'App\\Http\\Controllers\\EnrollmentController@show');
    $router->put('/enrollments/{id}', 'App\\Http\\Controllers\\EnrollmentController@update');
    $router->delete('/enrollments/{id}', 'App\\Http\\Controllers\\EnrollmentController@destroy');

    // Invoice CRUD
    $router->get('/invoices', 'App\\Http\\Controllers\\InvoiceController@index');
    $router->post('/invoices', 'App\\Http\\Controllers\\InvoiceController@store');
    $router->get('/invoices/{id}', 'App\\Http\\Controllers\\InvoiceController@show');
    $router->put('/invoices/{id}', 'App\\Http\\Controllers\\InvoiceController@update');
    $router->delete('/invoices/{id}', 'App\\Http\\Controllers\\InvoiceController@destroy');

    // Payment CRUD
    $router->get('/payments', 'App\\Http\\Controllers\\PaymentController@index');
    $router->post('/payments', 'App\\Http\\Controllers\\PaymentController@store');
    $router->get('/payments/{id}', 'App\\Http\\Controllers\\PaymentController@show');
    $router->put('/payments/{id}', 'App\\Http\\Controllers\\PaymentController@update');
    $router->delete('/payments/{id}', 'App\\Http\\Controllers\\PaymentController@destroy');

    // Booking CRUD
    $router->get('/bookings', 'App\\Http\\Controllers\\BookingController@index');
    $router->post('/bookings', 'App\\Http\\Controllers\\BookingController@store');
    $router->get('/bookings/{id}', 'App\\Http\\Controllers\\BookingController@show');
    $router->put('/bookings/{id}', 'App\\Http\\Controllers\\BookingController@update');
    $router->delete('/bookings/{id}', 'App\\Http\\Controllers\\BookingController@destroy');

    // Task CRUD
    $router->get('/tasks', 'App\\Http\\Controllers\\TaskController@index');
    $router->post('/tasks', 'App\\Http\\Controllers\\TaskController@store');
    $router->get('/tasks/{id}', 'App\\Http\\Controllers\\TaskController@show');
    $router->put('/tasks/{id}', 'App\\Http\\Controllers\\TaskController@update');
    $router->delete('/tasks/{id}', 'App\\Http\\Controllers\\TaskController@destroy');
    $router->post('/tasks/{id}/complete', 'App\\Http\\Controllers\\TaskController@complete');

    // DiscountRule CRUD
    $router->get('/discount_rules', 'App\\Http\\Controllers\\DiscountRuleController@index');
    $router->post('/discount_rules', 'App\\Http\\Controllers\\DiscountRuleController@store');
    $router->get('/discount_rules/{id}', 'App\\Http\\Controllers\\DiscountRuleController@show');
    $router->put('/discount_rules/{id}', 'App\\Http\\Controllers\\DiscountRuleController@update');
    $router->delete('/discount_rules/{id}', 'App\\Http\\Controllers\\DiscountRuleController@destroy');

    // CertificateExport CRUD
    $router->get('/certificate_exports', 'App\\Http\\Controllers\\CertificateExportController@index');
    $router->post('/certificate_exports', 'App\\Http\\Controllers\\CertificateExportController@store');
    $router->get('/certificate_exports/{id}', 'App\\Http\\Controllers\\CertificateExportController@show');
    $router->put('/certificate_exports/{id}', 'App\\Http\\Controllers\\CertificateExportController@update');
    $router->delete('/certificate_exports/{id}', 'App\\Http\\Controllers\\CertificateExportController@destroy');

    // AttendanceRecord CRUD
    $router->get('/attendance_records', 'App\\Http\\Controllers\\AttendanceRecordController@index');
    $router->post('/attendance_records', 'App\\Http\\Controllers\\AttendanceRecordController@store');
    $router->get('/attendance_records/{id}', 'App\\Http\\Controllers\\AttendanceRecordController@show');
    $router->put('/attendance_records/{id}', 'App\\Http\\Controllers\\AttendanceRecordController@update');
    $router->delete('/attendance_records/{id}', 'App\\Http\\Controllers\\AttendanceRecordController@destroy');

    // WebhookEvent CRUD
    $router->get('/webhook_events', 'App\\Http\\Controllers\\WebhookEventController@index');
    $router->post('/webhook_events', 'App\\Http\\Controllers\\WebhookEventController@store');
    $router->get('/webhook_events/{id}', 'App\\Http\\Controllers\\WebhookEventController@show');
    $router->put('/webhook_events/{id}', 'App\\Http\\Controllers\\WebhookEventController@update');
    $router->delete('/webhook_events/{id}', 'App\\Http\\Controllers\\WebhookEventController@destroy');
    $router->post('/webhook_events/{id}/retry', 'App\\Http\\Controllers\\WebhookEventController@retry');

    // EmailLog CRUD
    $router->get('/email_logs', 'App\\Http\\Controllers\\EmailLogController@index');
    $router->post('/email_logs', 'App\\Http\\Controllers\\EmailLogController@store');
    $router->get('/email_logs/{id}', 'App\\Http\\Controllers\\EmailLogController@show');
    $router->put('/email_logs/{id}', 'App\\Http\\Controllers\\EmailLogController@update');
    $router->delete('/email_logs/{id}', 'App\\Http\\Controllers\\EmailLogController@destroy');

    // AuditLog (Read-only for admins)
    $router->get('/audit_logs', 'App\\Http\\Controllers\\AuditLogController@index');
    $router->get('/audit_logs/{id}', 'App\\Http\\Controllers\\AuditLogController@show');

    // Teacher attendance endpoints (simplified, teacher-facing)
    $router->get('/teacher/courses', 'App\\Http\\Controllers\\TeacherAttendanceController@getCourses');
    $router->get('/teacher/courses/{id}', 'App\\Http\\Controllers\\TeacherAttendanceController@getCourse');
    $router->get('/teacher/courses/{id}/attendance', 'App\\Http\\Controllers\\TeacherAttendanceController@getAttendance');
    $router->post('/teacher/courses/{id}/sessions', 'App\\Http\\Controllers\\TeacherAttendanceController@createSession');
    $router->post('/teacher/courses/{id}/attendance', 'App\\Http\\Controllers\\TeacherAttendanceController@saveAttendance');
    $router->get('/teacher/courses/{id}/summary', 'App\\Http\\Controllers\\TeacherAttendanceController@getCourseSummary');
    $router->get('/teacher/courses/{courseId}/students/{studentId}/attendance', 'App\\Http\\Controllers\\TeacherAttendanceController@getStudentAttendance');

    // Retool-specific endpoints (simplified arrays, no pagination wrapper)
    $router->get('/retool/students', 'App\\Http\\Controllers\\RetoolController@students');
    $router->get('/retool/course_offerings', 'App\\Http\\Controllers\\RetoolController@courseOfferings');
    $router->get('/retool/sessions', 'App\\Http\\Controllers\\RetoolController@sessions');
    $router->get('/retool/leads', 'App\\Http\\Controllers\\RetoolController@leads');
    $router->get('/retool/enrollments', 'App\\Http\\Controllers\\RetoolController@enrollments');
    $router->get('/retool/invoices', 'App\\Http\\Controllers\\RetoolController@invoices');
    $router->get('/retool/payments', 'App\\Http\\Controllers\\RetoolController@payments');
    $router->get('/retool/bookings', 'App\\Http\\Controllers\\RetoolController@bookings');
    $router->get('/retool/tasks', 'App\\Http\\Controllers\\RetoolController@tasks');
    $router->get('/retool/discount_rules', 'App\\Http\\Controllers\\RetoolController@discountRules');
    $router->get('/retool/certificate_exports', 'App\\Http\\Controllers\\RetoolController@certificateExports');
    $router->get('/retool/attendance_records', 'App\\Http\\Controllers\\RetoolController@attendanceRecords');
    $router->get('/retool/webhook_events', 'App\\Http\\Controllers\\RetoolController@webhookEvents');
    $router->get('/retool/email_logs', 'App\\Http\\Controllers\\RetoolController@emailLogs');
    $router->get('/retool/audit_logs', 'App\\Http\\Controllers\\RetoolController@auditLogs');
    $router->get('/retool/all', 'App\\Http\\Controllers\\RetoolController@all');
    // additional protected endpoints can be added here
});
