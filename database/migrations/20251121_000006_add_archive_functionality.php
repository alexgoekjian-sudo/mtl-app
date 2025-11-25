<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddArchiveFunctionality extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Add is_active column to students table
        Schema::table('students', function (Blueprint $table) {
            $table->boolean('is_active')->default(1)->after('profile_notes');
        });

        // Add status column to course_offerings table
        DB::statement("ALTER TABLE course_offerings ADD COLUMN status ENUM('draft', 'active', 'completed', 'cancelled') NOT NULL DEFAULT 'active' AFTER `online`");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Remove is_active column from students
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });

        // Remove status column from course_offerings
        Schema::table('course_offerings', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
}
