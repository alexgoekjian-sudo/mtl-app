<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

if (! class_exists('AddApiTokenToUsers')) {
    class AddApiTokenToUsers extends Migration
    {
        public function up()
        {
            Schema::table('users', function (Blueprint $table) {
                $table->string('api_token', 80)->nullable()->unique()->after('password');
                $table->timestamp('api_token_created_at')->nullable()->after('api_token');
            });
        }

        public function down()
        {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn(['api_token', 'api_token_created_at']);
            });
        }
    }
}
