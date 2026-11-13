<?php

namespace Database\Seeders;

use App\Models\AssignmentSubmission;
use App\Models\LearningItem;
use App\Models\SchoolClass;
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
        User::updateOrCreate(
            ['email' => 'admin@falcon.sch.id'],
            [
                'name' => 'Administrator',
                'username' => 'admin',
                'role' => 'admin',
                'must_change_password' => false,
                'password' => 'admin123',
            ],
        );

        // Guru (Teacher)
        $guru = User::updateOrCreate(
            ['email' => 'budi@falcon.sch.id'],
            [
                'name' => 'Budi Santoso, S.Pd.',
                'username' => 'guru',
                'role' => 'guru',
                'must_change_password' => false,
                'password' => 'guru123',
            ],
        );

        // Siswa 1 (Ready-to-use demo student)
        $siswaAktif = User::updateOrCreate(
            ['email' => 'fauzi@falcon.sch.id'],
            [
                'name' => 'Ahmad Fauzi',
                'username' => 'siswa',
                'role' => 'siswa',
                'must_change_password' => false,
                'password' => 'siswa123',
            ],
        );

        // Siswa 2 (New student requiring initial password change)
        $siswaBaru = User::updateOrCreate(
            ['email' => 'siti@falcon.sch.id'],
            [
                'name' => 'Siti Nurhaliza',
                'username' => 'siswa_baru',
                'role' => 'siswa',
                'must_change_password' => true,
                'password' => 'siswa123',
            ],
        );

        // Seed Sample Classes for Guru
        $kelasRpl = SchoolClass::updateOrCreate(
            ['code' => 'RPL10A'],
            [
                'name' => 'X RPL 1',
                'subject' => 'Pemrograman Web & PerangkatBergerak',
                'room' => 'Lab Komputer 1',
                'description' => 'Kelas dasar pengembangan aplikasi web intranet sekolah menggunakan HTML, CSS, dan Laravel.',
                'guru_id' => $guru->id,
            ],
        );

        $kelasMat = SchoolClass::updateOrCreate(
            ['code' => 'MTK10A'],
            [
                'name' => 'X RPL 1',
                'subject' => 'Matematika Terapan',
                'room' => 'Ruang 104',
                'description' => 'Logika algoritma, matriks, dan himpunan untuk pemrograman komputer.',
                'guru_id' => $guru->id,
            ],
        );

        // Enroll Siswa
        $kelasRpl->students()->syncWithoutDetaching([$siswaAktif->id, $siswaBaru->id]);
        $kelasMat->students()->syncWithoutDetaching([$siswaAktif->id]);

        // Seed Sample Learning Items
        $pengumuman = LearningItem::updateOrCreate(
            ['title' => 'Selamat Datang di Semester Ganjil', 'user_id' => $guru->id],
            [
                'type' => 'announcement',
                'topic' => 'Informasi Kelas',
                'content' => 'Silakan unduh modul praktikum pada menu Pustaka dan kerjakan Tugas 1 sebelum tenggat waktu.',
                'max_score' => 100,
            ],
        );

        $modul1 = LearningItem::updateOrCreate(
            ['title' => 'Modul 1: Arsitektur Jaringan Intranet & HTTP', 'user_id' => $guru->id],
            [
                'type' => 'material',
                'topic' => 'Bab 1: Dasar Jaringan',
                'content' => 'Ringkasan cara kerja server lokal sekolah (LAN), IP lokal, dan komunikasi client-server tanpa koneksi internet.',
                'max_score' => 100,
            ],
        );

        $tugas1 = LearningItem::updateOrCreate(
            ['title' => 'Tugas 1: Analisis Topologi Server Lokal', 'user_id' => $guru->id],
            [
                'type' => 'assignment',
                'topic' => 'Bab 1: Dasar Jaringan',
                'content' => 'Jelaskan keuntungan penggunaan LMS di jaringan intranet lokal sekolah dibandingkan cloud publik dari sisi kecepatan dan privasi data.',
                'due_at' => now()->addDays(5),
                'max_score' => 100,
            ],
        );

        $kuis1 = LearningItem::updateOrCreate(
            ['title' => 'Kuis 1: Logika Dasar & Algoritma', 'user_id' => $guru->id],
            [
                'type' => 'quiz',
                'topic' => 'Bab 1: Logika',
                'content' => 'Tuliskan tabel kebenaran untuk operasi konjungsi (AND) dan disjungsi (OR) beserta contoh penerapannya pada percabangan program.',
                'due_at' => now()->addDays(3),
                'max_score' => 100,
            ],
        );

        // Share the sample items to their classes
        $pengumuman->classes()->syncWithoutDetaching([$kelasRpl->id]);
        $modul1->classes()->syncWithoutDetaching([$kelasRpl->id, $kelasMat->id]);
        $tugas1->classes()->syncWithoutDetaching([$kelasRpl->id]);
        $kuis1->classes()->syncWithoutDetaching([$kelasMat->id]);

        // Seed 1 graded submission for Kuis 1 so Nilai page shows immediate data
        AssignmentSubmission::updateOrCreate(
            [
                'learning_item_id' => $kuis1->id,
                'siswa_id' => $siswaAktif->id,
            ],
            [
                'answer_text' => 'Konjungsi (AND) bernilai benar hanya jika kedua pernyataan benar. Disjungsi (OR) bernilai benar jika salah satu pernyataan benar.',
                'score' => 95,
                'feedback' => 'Sangat tepat dan jelas! Pertahankan pemahaman konsep logikamu.',
                'submitted_at' => now()->subDay(),
                'graded_at' => now()->subHours(12),
            ],
        );
    }
}
