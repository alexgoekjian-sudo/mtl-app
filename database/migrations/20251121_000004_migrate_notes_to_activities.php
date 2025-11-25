<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class MigrateNotesToActivities extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Migrate existing activity_notes from leads table to activities
        $leads = DB::table('leads')
            ->whereNotNull('activity_notes')
            ->where('activity_notes', '!=', '')
            ->get();

        foreach ($leads as $lead) {
            DB::table('activities')->insert([
                'related_entity_type' => 'Lead',
                'related_entity_id' => $lead->id,
                'activity_type' => 'note',
                'subject' => 'Historical Notes',
                'body' => $lead->activity_notes,
                'created_by_user_id' => null, // Unknown historical user
                'created_at' => $lead->created_at ?? now(),
                'updated_at' => $lead->updated_at ?? now(),
            ]);
        }

        // Migrate existing profile_notes from students table to activities
        $students = DB::table('students')
            ->whereNotNull('profile_notes')
            ->where('profile_notes', '!=', '')
            ->get();

        foreach ($students as $student) {
            DB::table('activities')->insert([
                'related_entity_type' => 'Student',
                'related_entity_id' => $student->id,
                'activity_type' => 'note',
                'subject' => 'Historical Profile Notes',
                'body' => $student->profile_notes,
                'created_by_user_id' => null, // Unknown historical user
                'created_at' => $student->created_at ?? now(),
                'updated_at' => $student->updated_at ?? now(),
            ]);
        }

        // Note: We keep the activity_notes and profile_notes columns for backward compatibility
        // They can be deprecated in a future migration once the UI is updated to use activities table
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Delete migrated activities
        DB::table('activities')
            ->where('subject', 'Historical Notes')
            ->orWhere('subject', 'Historical Profile Notes')
            ->delete();
    }
}
