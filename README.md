# Falcon LMS

LMS untuk intranet sekolah dengan tampilan seperti **Google Drive** dan kelas yang diatur seperti **Google Classroom**.
Pusatnya tombol besar **+ Baru** yang paham posisi halaman: dari Beranda, materi masuk ke Pustaka; dari dalam kelas,
materi masuk ke kelas itu.

- Rancangan lengkap, temuan audit, dan roadmap: [docs/AUDIT.md](docs/AUDIT.md)
- Instalasi di server sekolah (Mac mini): [docs/INSTALL-MACOS.md](docs/INSTALL-MACOS.md)

## Status

**Fase 0 (fondasi) dan Fase 1 (MVP Pustaka + Kelas ala Classroom) selesai.** Rincian dan catatannya ada di
[docs/AUDIT.md §6](docs/AUDIT.md#6-roadmap).

- **Login** dengan username atau NIS, peran Admin, Guru, Siswa. Tidak ada registrasi mandiri dan tidak ada fitur
  lewat email, karena intranet sekolah tidak punya server email.
- **Tombol + Baru yang paham posisi halaman.** Dari Beranda atau Pustaka Saya, materi masuk ke Pustaka Saya (folder
  yang sedang dibuka). Dari dalam kelas, materi masuk ke kelas itu (topik yang sedang dipilih).
- **Pustaka Saya** ala Google Drive: folder bertingkat, unggah file dan folder (juga drag & drop), materi dengan
  editor teks, tautan, dan preview gambar, video, audio, dan PDF.
- **Kelas ala Google Classroom:** kode kelas, tab Forum, Tugas Kelas dengan Topik (urutan bisa digeser), dan Anggota.
  "Dari Pustaka…" menyalin materi ke kelas; satu folder menjadi satu topik.
- **Tugas:** siswa melampirkan file dan menyerahkan, guru menilai dan mengembalikan dengan catatan. Ada halaman
  To-do dan notifikasi di aplikasi.
- **Admin:** kelola pengguna, impor CSV dengan slip password yang bisa dicetak, dan reset password.
- **Backup harian otomatis** database dan file ke disk terpisah.
- Berjalan tanpa internet: font dibundel lokal dan tidak ada aset dari CDN.

**Berikutnya, Fase 2:** kuis dengan mode ujian PTS/PAS, rubrik, tab Nilai, kalender, Pustaka Sekolah, Sampah, dan
pencarian.

## Kebutuhan

- PHP 8.4 dengan ekstensi `pdo_pgsql` (dan `redis` untuk production)
- Composer 2
- Node 22 dan npm
- PostgreSQL 17

## Menjalankan di komputer developer

```bash
composer install
cp .env.example .env          # sesuaikan DB_USERNAME dan DB_PASSWORD
php artisan key:generate
php artisan migrate --seed    # membuat akun demo
npm ci
composer dev                  # server Laravel + Vite
```

`npm run build` menjalankan `php artisan wayfinder:generate`, jadi `composer install` harus dijalankan lebih dulu.

Akun demo dari seeder (password semuanya `password`):

| Username | Peran         |
| -------- | ------------- |
| `admin`  | Administrator |
| `guru`   | Guru          |
| `siswa`  | Siswa         |

## Mengelola akun

```bash
# Akun baru dengan password sementara (peran: admin, teacher, student, guardian)
php artisan falcon:create-user 0012345678 "Budi Santoso" --role=student

# Lupa password: buat password sementara baru
php artisan falcon:reset-password 0012345678
```

Biasanya akun dikelola dari halaman **Pengguna** di aplikasi. Perintah di atas untuk keadaan darurat, misalnya admin
lupa password.

## Layanan latar belakang

Notifikasi dikirim lewat antrean dan backup berjalan dari scheduler, jadi keduanya harus menyala:

```bash
php artisan queue:work          # di production: LaunchDaemon com.falconlms.queue
php artisan schedule:work       # di production: LaunchDaemon com.falconlms.scheduler
php artisan falcon:backup       # backup manual ke FALCON_BACKUP_PATH
```

`composer dev` sudah menjalankan queue worker untuk development.

## Pengecekan

Falcon **tidak memakai GitHub Actions** (supaya tidak ada biaya). Semua pengecekan dijalankan di komputer developer:

```bash
composer ci:check      # semuanya sekaligus: format, lint, tipe, Pint, PHPStan, dan Pest
npm run check          # format dan lint frontend (termasuk file Markdown)
npm run types:check    # vue-tsc
composer test          # Pint, PHPStan, dan Pest
```

Secara bawaan Pest memakai SQLite in-memory supaya cepat. Sebelum membuat rilis, jalankan juga test terhadap
PostgreSQL, sama seperti server sekolah:

```bash
DB_CONNECTION=pgsql DB_DATABASE=falcon_test php artisan test
```

`scripts/build-release.sh` otomatis menjalankan `composer ci:check`, `composer audit`, dan `npm audit` sebelum
membuat paket, dan berhenti jika ada yang gagal.

## Paket rilis offline

Server sekolah tidak terhubung ke internet. Paket rilis dibuat di komputer yang punya internet dengan
`scripts/build-release.sh`, lalu dipasang di server dengan `scripts/install-release.sh`.
Lihat [docs/INSTALL-MACOS.md](docs/INSTALL-MACOS.md).
