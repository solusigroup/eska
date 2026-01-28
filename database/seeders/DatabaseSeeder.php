<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create default superuser (tanpa factory untuk production)
        // Note: password_hash sudah di-cast sebagai 'hashed' di model User
        User::updateOrCreate(
            ['nama_user' => 'admin'],
            [
                'password_hash' => 'admin123',  // akan auto-hash oleh cast
                'role' => 'superuser',
                'jabatan' => 'Administrator',
            ]
        );

        $this->command->info('Default admin user created: admin / admin123');

        $this->call([
            CoaDagangSeeder::class,
            PerusahaanSeeder::class,
        ]);
    }
}
