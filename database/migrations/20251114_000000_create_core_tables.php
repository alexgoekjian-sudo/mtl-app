<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

if (! class_exists('CreateCoreTables')) {
    class CreateCoreTables extends Migration
    {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // users (teachers/managers/admin)
        Schema::create('users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('email')->unique()->nullable();
            $table->string('password')->nullable();
            $table->string('role')->default('teacher'); // teacher, manager, admin, reception
            $table->string('phone')->nullable();
            $table->json('availability')->nullable();
            $table->timestamps();
        });

        // leads
        Schema::create('leads', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('source')->nullable();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('country')->nullable();
            $table->string('languages')->nullable();
            $table->text('activity_notes')->nullable();
            $table->timestamps();
        });

        // students
        Schema::create('students', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('lead_id')->nullable();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('initial_level')->nullable();
            $table->string('current_level')->nullable();
            $table->text('profile_notes')->nullable();
            $table->timestamps();
            $table->foreign('lead_id')->references('id')->on('leads')->onDelete('set null');
        });

        // course_offerings
        Schema::create('course_offerings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('course_key')->unique();
            $table->string('course_full_name');
            $table->string('level')->nullable();
            $table->string('program')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->integer('hours_total')->nullable();
            $table->json('schedule')->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->integer('capacity')->nullable();
            $table->string('location')->nullable();
            $table->boolean('online')->default(false);
            $table->timestamps();
        });

        // sessions
        Schema::create('sessions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('course_offering_id');
            $table->date('date');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->unsignedBigInteger('teacher_id')->nullable();
            $table->timestamps();
            $table->foreign('course_offering_id')->references('id')->on('course_offerings')->onDelete('cascade');
            $table->foreign('teacher_id')->references('id')->on('users')->onDelete('set null');
        });

        // enrollments
        Schema::create('enrollments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('course_offering_id');
            $table->enum('status', ['registered','active','cancelled','completed'])->default('registered');
            $table->timestamp('enrolled_at')->nullable();
            $table->timestamps();
            $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');
            $table->foreign('course_offering_id')->references('id')->on('course_offerings')->onDelete('cascade');
        });

        // attendance_records
        Schema::create('attendance_records', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('session_id');
            $table->unsignedBigInteger('student_id');
            $table->enum('status', ['present','late','absent','excused'])->default('present');
            $table->text('note')->nullable();
            $table->unsignedBigInteger('recorded_by')->nullable();
            $table->timestamp('recorded_at')->nullable();
            $table->timestamps();
            $table->foreign('session_id')->references('id')->on('sessions')->onDelete('cascade');
            $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');
            $table->foreign('recorded_by')->references('id')->on('users')->onDelete('set null');
        });

        // invoices
        Schema::create('invoices', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('invoice_number')->unique();
            $table->unsignedBigInteger('billing_contact_id')->nullable();
            $table->json('items')->nullable();
            $table->decimal('total', 10, 2)->default(0);
            $table->enum('status', ['draft','sent','paid','overdue'])->default('draft');
            $table->date('issued_date')->nullable();
            $table->date('due_date')->nullable();
            $table->timestamps();
        });

        // payments
        Schema::create('payments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->decimal('amount', 10, 2);
            $table->string('method')->nullable();
            $table->string('external_reference')->nullable();
            $table->timestamp('recorded_at')->nullable();
            $table->timestamps();
            $table->foreign('invoice_id')->references('id')->on('invoices')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('attendance_records');
        Schema::dropIfExists('enrollments');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('course_offerings');
        Schema::dropIfExists('students');
        Schema::dropIfExists('leads');
        Schema::dropIfExists('users');
    }
    }
}
