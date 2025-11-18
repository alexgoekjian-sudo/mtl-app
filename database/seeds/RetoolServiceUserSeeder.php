<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RetoolServiceUserSeeder extends Seeder
{
    /**
     * Seed a dedicated Retool service user with a static API token.
     *
     * @return void
     */
    public function run()
    {
        $now = date('Y-m-d H:i:s');
        $email = 'retool@mixtreelang.nl';

        // Check if user already exists
        $existingUser = DB::table('users')->where('email', $email)->first();

        if ($existingUser) {
            // User exists, show the existing token
            $this->command->warn('Retool service user already exists!');
            $this->command->info('Email: ' . $email);
            $this->command->warn('Existing API Token: ' . $existingUser->api_token);
            $this->command->info('Add this token to your Retool resource as:');
            $this->command->line('Authorization: Bearer ' . $existingUser->api_token);
            return;
        }

        // Generate new token for Retool
        $retoolToken = bin2hex(random_bytes(24));

        try {
            DB::table('users')->insert([
                'name' => 'Retool Service',
                'email' => $email,
                'password' => '', // empty string instead of null
                'role' => 'retool',
                'api_token' => $retoolToken,
                'api_token_created_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $this->command->info('===================================');
            $this->command->info('Retool service user created successfully!');
            $this->command->info('===================================');
            $this->command->info('Email: ' . $email);
            $this->command->warn('API Token: ' . $retoolToken);
            $this->command->info('Add this token to your Retool resource as:');
            $this->command->line('Authorization: Bearer ' . $retoolToken);
            $this->command->info('===================================');
        } catch (\Exception $e) {
            $this->command->error('Failed to create retool user!');
            $this->command->error('Error: ' . $e->getMessage());
        }
    }
}
