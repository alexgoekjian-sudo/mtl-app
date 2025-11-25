<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class EnhanceEnrollmentWorkflow extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('enrollments', function (Blueprint $table) {
            // Modify status enum to add 'pending'
            // Note: In MySQL, we need to use raw SQL to modify ENUM
        });

        // Modify ENUM using raw SQL (Laravel doesn't support ENUM modification directly)
        DB::statement("ALTER TABLE enrollments MODIFY COLUMN status ENUM('pending', 'registered', 'active', 'cancelled', 'completed') NOT NULL DEFAULT 'pending'");

        Schema::table('enrollments', function (Blueprint $table) {
            // Add is_historical flag (if not already added by previous migration)
            if (!Schema::hasColumn('enrollments', 'is_historical')) {
                $table->boolean('is_historical')->default(false)->after('is_trial')->comment('Imported from historical data');
                $table->index('is_historical', 'idx_enrollment_is_historical');
            }

            // Add payment override fields
            $table->text('payment_override_reason')->nullable()->after('historical_metadata')
                ->comment('Reason for manual payment approval (e.g., pay after course start)');

            // Add course transfer tracking
            $table->unsignedBigInteger('transferred_from_enrollment_id')->nullable()->after('payment_override_reason')
                ->comment('Original enrollment if this is result of transfer');
            $table->unsignedBigInteger('transferred_to_enrollment_id')->nullable()->after('transferred_from_enrollment_id')
                ->comment('Target enrollment if student was transferred out');

            // Add indexes for performance
            $table->index('status', 'idx_enrollment_status');

            // Add foreign keys for transfer tracking
            $table->foreign('transferred_from_enrollment_id', 'fk_enrollment_transferred_from')
                ->references('id')->on('enrollments')->onDelete('set null');
            $table->foreign('transferred_to_enrollment_id', 'fk_enrollment_transferred_to')
                ->references('id')->on('enrollments')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('enrollments', function (Blueprint $table) {
            // Drop foreign keys first
            $table->dropForeign('fk_enrollment_transferred_from');
            $table->dropForeign('fk_enrollment_transferred_to');

            // Drop indexes
            $table->dropIndex('idx_enrollment_status');
            if (Schema::hasColumn('enrollments', 'is_historical')) {
                $table->dropIndex('idx_enrollment_is_historical');
            }

            // Drop columns
            $table->dropColumn([
                'payment_override_reason',
                'transferred_from_enrollment_id',
                'transferred_to_enrollment_id'
            ]);

            // Only drop is_historical if it was added by this migration
            // (check if it exists and was not added by previous migration)
            if (Schema::hasColumn('enrollments', 'is_historical')) {
                // Check migration history to determine if we should drop it
                // For safety, we'll leave it (comment out the drop)
                // $table->dropColumn('is_historical');
            }
        });

        // Restore original ENUM (without 'pending')
        DB::statement("ALTER TABLE enrollments MODIFY COLUMN status ENUM('registered', 'active', 'cancelled', 'completed') NOT NULL DEFAULT 'registered'");
    }
}
