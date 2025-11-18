<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCourseOfferingIdentifiers extends Migration
{
    /**
     * Add attendance_id, round, course_book to course_offerings
     * Change course_key from unique identifier to non-unique course type
     */
    public function up()
    {
        Schema::table('course_offerings', function (Blueprint $table) {
            // Add unique attendance_id - this is what was in ATTENDANCE_COURSE_NAME (e.g., "B1_EVE _EDMON_1")
            $table->string('attendance_id')->unique()->after('id');
            
            // Add round number (1, 2, 3, etc.) - from ROUND column
            $table->integer('round')->default(1)->after('attendance_id');
            
            // Add course_book field - from COURSE_BOOK column (ISBN and title)
            $table->text('course_book')->nullable()->after('book_included');
            
            // course_key now represents the course type (e.g., "B1 EVE ONLINE") - NOT unique
            // This is extracted from COURSE_SHORT_NAME in CSV
        });

        // Note: course_key is already in the table from previous migration, no need to add it
        // It will be populated with non-unique values from COURSE_SHORT_NAME
    }

    /**
     * Reverse the migrations
     */
    public function down()
    {
        Schema::table('course_offerings', function (Blueprint $table) {
            $table->dropUnique(['attendance_id']);
            $table->dropColumn(['attendance_id', 'round', 'course_book']);
        });
    }
}
