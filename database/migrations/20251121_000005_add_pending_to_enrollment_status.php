<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddPendingToEnrollmentStatus extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Modify the status ENUM to include 'pending'
        DB::statement("ALTER TABLE enrollments MODIFY COLUMN status ENUM('pending', 'registered', 'active', 'completed', 'dropped') NOT NULL DEFAULT 'registered'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Remove 'pending' from the ENUM
        DB::statement("ALTER TABLE enrollments MODIFY COLUMN status ENUM('registered', 'active', 'completed', 'dropped') NOT NULL DEFAULT 'registered'");
    }
}
