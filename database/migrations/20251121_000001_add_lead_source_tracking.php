<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddLeadSourceTracking extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('leads', function (Blueprint $table) {
            // Add reference column (lead type/channel)
            $table->enum('reference', [
                'online_form',
                'level_check',
                'phone_call',
                'walk_in',
                'referral',
                'other'
            ])->nullable()->after('source')->comment('Lead origin type');

            // Add source_detail column (marketing attribution)
            $table->enum('source_detail', [
                'google',
                'facebook',
                'instagram',
                'ai',
                'linkedin',
                'referral_name',
                'website_direct',
                'other'
            ])->nullable()->after('reference')->comment('Marketing source attribution');

            // Add index for analytics queries
            $table->index('reference', 'idx_lead_reference');
            $table->index('source_detail', 'idx_lead_source_detail');
        });

        // Optionally migrate existing 'source' data to 'reference' if mapping is clear
        // Uncomment and adjust if you want to migrate existing data:
        /*
        DB::table('leads')->where('source', 'website')->update(['reference' => 'online_form']);
        DB::table('leads')->where('source', 'referral')->update(['reference' => 'referral']);
        DB::table('leads')->where('source', 'walk-in')->update(['reference' => 'walk_in']);
        */
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropIndex('idx_lead_reference');
            $table->dropIndex('idx_lead_source_detail');
            $table->dropColumn(['reference', 'source_detail']);
        });
    }
}
