<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create default superuser (tanpa factory untuk production)
        User::updateOrCreate(
            ['nama_user' => 'admin'],
            [
                'password_hash' => Hash::make('admin123'),
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
