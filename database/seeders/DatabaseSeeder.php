<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database with demo accounts for local development.
     *
     * Every demo account uses the password "password".
     */
    public function run(): void
    {
        User::factory()->admin()->create([
            'name' => 'Administrator',
            'username' => 'admin',
        ]);

        User::factory()->teacher()->create([
            'name' => 'Ibu Guru Demo',
            'username' => 'guru',
        ]);

        User::factory()->create([
            'name' => 'Siswa Demo',
            'username' => 'siswa',
        ]);
    }
}
