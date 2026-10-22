# Falcon LMS

LMS untuk intranet sekolah dengan tampilan seperti **Google Drive** dan kelas yang diatur seperti **Google Classroom**.
Pusatnya tombol besar **+ Baru** yang paham posisi halaman: dari Beranda, materi masuk ke Pustaka; dari dalam kelas,
materi masuk ke kelas itu.

- Rancangan lengkap, temuan audit, dan roadmap: [docs/AUDIT.md](docs/AUDIT.md)
- Instalasi di server sekolah (Mac mini): [docs/INSTALL-MACOS.md](docs/INSTALL-MACOS.md)

## Status

**Fase 0 (fondasi) selesai:**

- Login dengan username atau NIS. Tidak ada registrasi mandiri dan tidak ada fitur lewat email, karena intranet sekolah
  tidak punya server email.
- Peran Admin, Guru, Siswa (Orang Tua disiapkan untuk tahap berikutnya).
- Akun baru dan reset password memakai password sementara yang wajib diganti saat login berikutnya.
- Layout ala Drive: sidebar, tombol **+ Baru** (juga sebagai tombol mengambang di HP), breadcrumb, dan Beranda.
  Isi menu + Baru dikirim server sesuai halaman dan peran. Menyimpan konten hadir di Fase 1.
- Berjalan tanpa internet: font dibundel lokal dan tidak ada aset dari CDN.
- PostgreSQL sebagai database, dan test di CI juga memakai PostgreSQL.

**Berikutnya, Fase 1:** Pustaka Saya, kelas dengan Topik, tugas, kelola pengguna, dan backup harian.

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

## Pengecekan

```bash
npm run check          # format dan lint frontend (termasuk file Markdown)
npm run types:check    # vue-tsc
composer test          # Pint, PHPStan, dan Pest
```

Secara bawaan Pest memakai SQLite in-memory supaya cepat. CI menjalankan test yang sama terhadap PostgreSQL. Untuk
melakukannya di komputer sendiri, set `DB_CONNECTION=pgsql` dan `DB_DATABASE` ke database test sebelum `php artisan test`.

## Paket rilis offline

Server sekolah tidak terhubung ke internet. Paket rilis dibuat di komputer yang punya internet dengan
`scripts/build-release.sh`, lalu dipasang di server dengan `scripts/install-release.sh`.
Lihat [docs/INSTALL-MACOS.md](docs/INSTALL-MACOS.md).
