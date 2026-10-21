<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Administrator
        User::factory()->admin()->create([
            'name' => 'Administrator',
            'email' => 'admin@falcon.sch.id',
            'username' => 'admin',
            'password' => 'admin123',
        ]);

        // Guru (Teacher)
        User::factory()->guru()->create([
            'name' => 'Budi Santoso, S.Pd.',
            'email' => 'budi@falcon.sch.id',
            'username' => 'guru',
            'password' => 'guru123',
        ]);

        // Siswa (Student with required initial password change)
        User::factory()->siswa()->mustChangePassword()->create([
            'name' => 'Ahmad Fauzi',
            'email' => 'fauzi@falcon.sch.id',
            'username' => 'siswa',
            'password' => 'siswa123',
        ]);
    }
}
