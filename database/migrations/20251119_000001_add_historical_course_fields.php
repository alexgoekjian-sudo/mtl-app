<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddHistoricalCourseFields extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Add is_historical flag to course_offerings
        Schema::table('course_offerings', function (Blueprint $table) {
            $table->boolean('is_historical')->default(false)->after('online');
            $table->index('is_historical');
        });

        // Add historical_metadata to enrollments
        Schema::table('enrollments', function (Blueprint $table) {
            $table->json('historical_metadata')->nullable()->after('status');
        });

        // Add index on students.email for faster matching
        Schema::table('students', function (Blueprint $table) {
            $table->index('email');
        });

        // Add composite index on enrollments for duplicate checking
        Schema::table('enrollments', function (Blueprint $table) {
            $table->index(['student_id', 'course_offering_id'], 'idx_student_course');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('course_offerings', function (Blueprint $table) {
            $table->dropIndex(['is_historical']);
            $table->dropColumn('is_historical');
        });

        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropColumn('historical_metadata');
            $table->dropIndex('idx_student_course');
        });

        Schema::table('students', function (Blueprint $table) {
            $table->dropIndex(['email']);
        });
    }
}
