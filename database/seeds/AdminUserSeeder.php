<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $now = date('Y-m-d H:i:s');

        // Default admin user - change password after first login
        DB::table('users')->insert([
            'name' => 'Admin',
            'email' => 'admin@mixtreelang.nl',
            'password' => password_hash('ChangeMe123!', PASSWORD_BCRYPT),
            'role' => 'admin',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}
