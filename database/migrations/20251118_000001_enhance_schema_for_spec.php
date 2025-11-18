<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class EnhanceSchemaForSpec extends Migration
{
    /**
     * Run the migrations - add missing tables and columns per spec requirements
     */
    public function up()
    {
        // 1. Add missing columns to existing tables
        
        // Students - add spec-required fields
        Schema::table('students', function (Blueprint $table) {
            $table->string('country_of_origin')->nullable()->after('phone');
            $table->string('city_of_residence')->nullable()->after('country_of_origin');
            $table->date('dob')->nullable()->after('city_of_residence');
            $table->json('languages')->nullable()->after('dob'); // array of languages
            $table->text('previous_courses')->nullable()->after('languages');
        });

        // Course Offerings - add economic fields and type
        Schema::table('course_offerings', function (Blueprint $table) {
            $table->decimal('teacher_hourly_rate', 10, 2)->nullable()->after('price');
            $table->decimal('classroom_cost', 10, 2)->nullable()->after('teacher_hourly_rate');
            $table->decimal('admin_overhead', 10, 2)->nullable()->after('classroom_cost');
            $table->string('type')->nullable()->after('program'); // morning/afternoon/online/intensive/1-2-1
            $table->boolean('book_included')->default(true)->after('type');
        });

        // Enrollments - add mid-course assessment and trial fields
        Schema::table('enrollments', function (Blueprint $table) {
            $table->timestamp('dropped_at')->nullable()->after('enrolled_at');
            $table->string('mid_course_level')->nullable()->after('status');
            $table->text('mid_course_notes')->nullable()->after('mid_course_level');
            $table->boolean('is_trial')->default(false)->after('mid_course_notes');
        });

        // Invoices - add student link and discount fields
        Schema::table('invoices', function (Blueprint $table) {
            $table->unsignedBigInteger('student_id')->nullable()->after('billing_contact_id');
            $table->decimal('discount_percent', 5, 2)->default(0)->after('total');
            $table->string('discount_reason')->nullable()->after('discount_percent');
            $table->foreign('student_id')->references('id')->on('students')->onDelete('set null');
        });

        // Payments - add status and refund flag
        Schema::table('payments', function (Blueprint $table) {
            $table->enum('status', ['pending','completed','refunded','failed'])->default('pending')->after('amount');
            $table->boolean('is_refund')->default(false)->after('status');
        });

        // 2. Create new tables

        // Level checks / bookings (Cal.com integration)
        Schema::create('bookings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('lead_id')->nullable();
            $table->unsignedBigInteger('student_id')->nullable();
            $table->string('booking_provider')->default('cal.com'); // cal.com, manual, other
            $table->string('external_booking_id')->nullable()->unique();
            $table->string('booking_type')->default('level_check'); // level_check, consultation, other
            $table->timestamp('scheduled_at')->nullable();
            $table->unsignedBigInteger('assigned_teacher_id')->nullable();
            $table->string('assigned_level')->nullable(); // outcome of level check
            $table->text('teacher_notes')->nullable();
            $table->enum('status', ['scheduled','completed','cancelled','no_show'])->default('scheduled');
            $table->json('webhook_payload')->nullable(); // raw Cal.com data
            $table->timestamps();
            $table->foreign('lead_id')->references('id')->on('leads')->onDelete('set null');
            $table->foreign('student_id')->references('id')->on('students')->onDelete('set null');
            $table->foreign('assigned_teacher_id')->references('id')->on('users')->onDelete('set null');
        });

        // Webhook events (idempotency and replay queue)
        Schema::create('webhook_events', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('provider'); // mollie, cal.com, etc
            $table->string('event_type'); // payment.paid, booking.created, etc
            $table->string('external_id')->unique(); // webhook id from provider
            $table->json('payload'); // full webhook body
            $table->enum('status', ['pending','processed','failed','ignored'])->default('pending');
            $table->integer('retry_count')->default(0);
            $table->timestamp('last_retry_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            $table->index(['provider', 'event_type']);
            $table->index('status');
        });

        // Email logs (outgoing communications)
        Schema::create('email_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('recipient_email');
            $table->string('recipient_name')->nullable();
            $table->string('subject');
            $table->text('body_html')->nullable();
            $table->text('body_text')->nullable();
            $table->string('template_name')->nullable(); // e.g., 'level_check_invite', 'payment_link', 'continuation_offer'
            $table->string('related_entity_type')->nullable(); // Lead, Student, Enrollment, Invoice
            $table->unsignedBigInteger('related_entity_id')->nullable();
            $table->enum('status', ['queued','sent','failed','bounced'])->default('queued');
            $table->timestamp('sent_at')->nullable();
            $table->text('error_message')->nullable();
            $table->unsignedBigInteger('sent_by_user_id')->nullable();
            $table->timestamps();
            $table->foreign('sent_by_user_id')->references('id')->on('users')->onDelete('set null');
            $table->index(['related_entity_type', 'related_entity_id']);
        });

        // Audit trail (change history for critical entities)
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('auditable_type'); // Invoice, Payment, Enrollment, AttendanceRecord
            $table->unsignedBigInteger('auditable_id');
            $table->string('event'); // created, updated, deleted
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamp('created_at');
            $table->index(['auditable_type', 'auditable_id']);
            $table->index('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });

        // Tasks/Activities (coordinator follow-ups, reminders)
        Schema::create('tasks', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('title');
            $table->text('body')->nullable();
            $table->unsignedBigInteger('assigned_to_user_id')->nullable();
            $table->string('related_entity_type')->nullable(); // Lead, Student, Enrollment, etc
            $table->unsignedBigInteger('related_entity_id')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->enum('status', ['pending','completed','cancelled'])->default('pending');
            $table->enum('priority', ['low','medium','high'])->default('medium');
            $table->timestamp('completed_at')->nullable();
            $table->unsignedBigInteger('created_by_user_id')->nullable();
            $table->timestamps();
            $table->foreign('assigned_to_user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('created_by_user_id')->references('id')->on('users')->onDelete('set null');
            $table->index(['related_entity_type', 'related_entity_id']);
            $table->index('status');
        });

        // Discount rules (configurable discounts: returning students, referrals, ad-hoc)
        Schema::create('discount_rules', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name'); // "Returning Student 5%", "Referral Discount", "Ad-hoc"
            $table->decimal('percent', 5, 2); // 5.00, 10.00, custom
            $table->string('rule_type'); // returning, referral, ad_hoc
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Certificate exports (track certificate generation eligibility and issued status)
        Schema::create('certificate_exports', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('course_offering_id');
            $table->decimal('attendance_percent', 5, 2);
            $table->boolean('eligible')->default(false); // attendance >= 80%
            $table->timestamp('exported_at')->nullable();
            $table->timestamp('issued_at')->nullable(); // when certificate was actually generated/sent
            $table->string('certificate_url')->nullable(); // if storing generated PDF
            $table->timestamps();
            $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');
            $table->foreign('course_offering_id')->references('id')->on('course_offerings')->onDelete('cascade');
            $table->index('eligible');
        });
    }

    /**
     * Reverse the migrations
     */
    public function down()
    {
        Schema::dropIfExists('certificate_exports');
        Schema::dropIfExists('discount_rules');
        Schema::dropIfExists('tasks');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('email_logs');
        Schema::dropIfExists('webhook_events');
        Schema::dropIfExists('bookings');

        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['status', 'is_refund']);
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['student_id']);
            $table->dropColumn(['student_id', 'discount_percent', 'discount_reason']);
        });

        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropColumn(['dropped_at', 'mid_course_level', 'mid_course_notes', 'is_trial']);
        });

        Schema::table('course_offerings', function (Blueprint $table) {
            $table->dropColumn(['teacher_hourly_rate', 'classroom_cost', 'admin_overhead', 'type', 'book_included']);
        });

        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn(['country_of_origin', 'city_of_residence', 'dob', 'languages', 'previous_courses']);
        });
    }
}
