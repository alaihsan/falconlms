<?php

namespace Database\Seeders;

use App\Actions\Courses\CreateCourse;
use App\Enums\ItemType;
use App\Enums\MemberRole;
use App\Models\Folder;
use App\Models\Item;
use App\Models\Space;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database with demo data for local development.
     *
     * Every demo account uses the password "password".
     */
    public function run(): void
    {
        User::factory()->admin()->create(['name' => 'Administrator', 'username' => 'admin']);
        $teacher = User::factory()->teacher()->create(['name' => 'Ibu Guru Demo', 'username' => 'guru']);
        $student = User::factory()->create(['name' => 'Siswa Demo', 'username' => 'siswa']);
        $classmates = User::factory()->count(5)->create();

        // The teacher's library, organised in folders like Google Drive.
        $library = Space::personalLibraryFor($teacher);
        $algebra = Folder::factory()->create(['space_id' => $library->id, 'name' => 'Bab 1 - Aljabar']);
        $functions = Folder::factory()->create(['space_id' => $library->id, 'name' => 'Bab 2 - Fungsi']);
        $exercises = Folder::factory()->in($functions)->create(['name' => 'Latihan']);

        Item::factory()->create(['space_id' => $library->id, 'folder_id' => $algebra->id, 'title' => 'Rumus dasar aljabar', 'body' => '<h2>Sifat distributif</h2><p>a(b + c) = ab + ac</p>']);
        Item::factory()->create(['space_id' => $library->id, 'folder_id' => $functions->id, 'title' => 'Pengertian fungsi', 'body' => '<p>Fungsi memetakan setiap anggota domain ke tepat satu anggota kodomain.</p>']);
        Item::factory()->assignment()->create(['space_id' => $library->id, 'folder_id' => $exercises->id, 'title' => 'Latihan domain dan range', 'due_at' => null]);
        Item::factory()->create(['space_id' => $library->id, 'type' => ItemType::Link, 'title' => 'GeoGebra sekolah', 'url' => 'http://geogebra.sekolah.lan', 'body' => null]);

        // A class organised in topics like Google Classroom.
        $course = app(CreateCourse::class)->handle($teacher, ['name' => 'Matematika X IPA 1', 'section' => 'Semester 1', 'subject' => 'Matematika']);
        $course->users()->attach($student, ['role' => MemberRole::Student]);
        $course->users()->attach($classmates, ['role' => MemberRole::Student]);

        $topic1 = $course->topics()->create(['name' => 'Bab 1 - Aljabar', 'position' => 0]);
        $topic2 = $course->topics()->create(['name' => 'Bab 2 - Fungsi', 'position' => 1]);

        Item::factory()->create(['space_id' => $course->id, 'title' => 'Tata tertib kelas', 'body' => '<p>Kumpulkan tugas tepat waktu.</p>', 'created_by' => $teacher->id]);
        Item::factory()->inTopic($topic1)->create(['title' => 'Rumus dasar aljabar', 'created_by' => $teacher->id]);
        Item::factory()->assignment()->inTopic($topic1)->create(['title' => 'Latihan 1', 'created_by' => $teacher->id, 'due_at' => now()->addDays(3)->setTime(23, 59)]);
        Item::factory()->inTopic($topic2)->create(['title' => 'Pengertian fungsi', 'created_by' => $teacher->id]);
        Item::factory()->assignment()->inTopic($topic2)->create(['title' => 'Apa itu domain?', 'created_by' => $teacher->id, 'due_at' => now()->addWeek()->setTime(23, 59)]);
    }
}
