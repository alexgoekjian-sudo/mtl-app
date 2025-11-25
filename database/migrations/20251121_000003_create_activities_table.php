<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateActivitiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('activities', function (Blueprint $table) {
            $table->id();

            // Polymorphic relation (can relate to Lead, Student, Enrollment, etc.)
            $table->string('related_entity_type')->comment('Lead, Student, Enrollment, etc.');
            $table->unsignedBigInteger('related_entity_id')->comment('ID of related entity');

            // Activity details
            $table->enum('activity_type', [
                'note',
                'call',
                'email',
                'meeting',
                'level_check',
                'payment',
                'enrollment',
                'other'
            ])->default('note');
            $table->string('subject')->nullable()->comment('Optional activity subject/title');
            $table->text('body')->nullable()->comment('Activity content/notes');

            // Audit fields
            $table->unsignedBigInteger('created_by_user_id')->nullable()->comment('User who created activity');
            $table->timestamps();

            // Indexes for performance
            $table->index(['related_entity_type', 'related_entity_id'], 'idx_activity_entity');
            $table->index('activity_type', 'idx_activity_type');
            $table->index('created_at', 'idx_activity_created_at');
            $table->index('created_by_user_id', 'idx_activity_created_by');

            // Foreign key for user
            $table->foreign('created_by_user_id', 'fk_activity_created_by')
                ->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('activities');
    }
}
