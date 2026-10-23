# Instalasi Falcon LMS di Mac mini

Panduan memasang Falcon di server sekolah: **Mac mini M2, RAM 8 GB, SSD 256 GB, dengan UPS**.
Latar belakang keputusan-keputusan di sini ada di [AUDIT.md §3.7 dan §3.8](AUDIT.md#37-deployment-intranet).

> **Status: draf.** Langkah-langkah ini disusun dari dokumentasi Homebrew, Nginx, PostgreSQL, dan Laravel.
> Skrip pemasangan sudah diuji dalam simulasi Linux, tapi seluruh panduan **belum dicoba langsung di Mac mini**.
> Jalankan sekali di Mac mini sebelum dipakai siswa, lalu catat dan perbaiki bagian yang berbeda.

## Gambaran

| Komponen      | Peran                               | Cara berjalan                          |
| ------------- | ----------------------------------- | -------------------------------------- |
| Nginx         | Menerima akses dari browser (HTTPS) | Layanan Homebrew, menyala saat boot    |
| PHP-FPM 8.4   | Menjalankan aplikasi Falcon         | Layanan Homebrew, menyala saat boot    |
| PostgreSQL 17 | Database                            | Layanan Homebrew, menyala saat boot    |
| Redis         | Session, cache, dan antrean         | Layanan Homebrew, menyala saat boot    |
| Queue worker  | Notifikasi dan pekerjaan latar      | LaunchDaemon `com.falconlms.queue`     |
| Scheduler     | Tugas terjadwal, termasuk backup    | LaunchDaemon `com.falconlms.scheduler` |

Struktur folder di server:

```
/opt/falcon/
├── current -> releases/falcon-<versi>   versi yang sedang aktif
├── releases/                            3 versi terakhir
└── shared/
    ├── .env                             pengaturan production
    ├── tls/                             sertifikat HTTPS
    └── storage/                         log, cache, dan file unggahan
        └── app -> /Volumes/FalconData/app   file materi di SSD eksternal
```

## 0. Yang perlu disiapkan

- Mac mini, **SSD eksternal** (USB-C/Thunderbolt) untuk file materi, UPS, dan kabel LAN.
- **Disk backup** terpisah dari SSD `FalconData`, misalnya HDD/SSD eksternal kedua atau folder NAS sekolah.
- **Koneksi internet sementara** saat instalasi awal, untuk memasang Homebrew dan paket-paketnya. Setelah selesai,
  Mac mini boleh diputus dari internet. Pembaruan Falcon berikutnya memakai paket rilis offline.
- Satu komputer lain yang punya internet (PHP 8.4, Composer, Node 22) untuk membuat paket rilis.

## 1. Pengaturan macOS

1. Buat akun administrator bernama **`falcon`**. Semua langkah berikut dijalankan dari akun ini.
2. **Jangan pernah sleep, dan menyala sendiri setelah listrik padam:**
    ```bash
    sudo pmset -a sleep 0 disksleep 0 autorestart 1
    ```
3. **Pembaruan macOS:** di _System Settings → General → Software Update → Automatic Updates_, matikan pemasangan
   otomatis. Pasang pembaruan secara manual di luar jam sekolah dan **di luar masa ujian**.
4. **FileVault aktif.** Karena sudah ada UPS, data siswa di disk sebaiknya dienkripsi. Konsekuensinya, setelah Mac
   menyala ulang (misalnya listrik padam lebih lama dari daya UPS), Mac berhenti di layar password dan Falcon belum
   jalan sampai petugas IT mengetik password akun `falcon`. Lihat [prosedur setelah listrik padam](#prosedur-setelah-listrik-padam).
5. **Firewall** aktif di _System Settings → Network → Firewall_.
6. **Jaringan:** pakai kabel LAN. Minta IP tetap (reservasi DHCP) dan nama domain lokal, misalnya `falcon.sekolah.lan`,
   di router atau DNS sekolah.
7. **SSD eksternal:** format sebagai APFS dengan nama `FalconData`, lalu buat foldernya:
    ```bash
    mkdir -p /Volumes/FalconData/app/private
    ```
8. **Disk backup:** format sebagai APFS dengan nama `FalconBackup`, lalu buat foldernya:
    ```bash
    mkdir -p /Volumes/FalconBackup/falcon
    ```

## 2. Pasang perangkat lunak (butuh internet sementara)

```bash
# Homebrew (ikuti petunjuk di layar untuk menambahkan brew ke PATH)
/bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"

brew install php@8.4 postgresql@17 redis nginx
brew link --force php@8.4

# Ekstensi Redis untuk PHP
pecl install redis

# Pastikan ekstensi yang dibutuhkan sudah ada
php -v                          # PHP 8.4.x
php -m | grep -E 'pdo_pgsql|redis'
```

Layanan dijalankan sebagai LaunchDaemon (`sudo`) supaya **menyala saat boot tanpa perlu login**. PostgreSQL dan
Redis dijalankan sebagai user `falcon`, bukan root:

```bash
sudo brew services start --sudo-service-user=falcon postgresql@17
sudo brew services start --sudo-service-user=falcon redis
```

> Opsi `--sudo-service-user` bisa berubah antar versi Homebrew. Periksa dengan `brew services --help`.

## 3. Database

```bash
createdb falcon
psql -d falcon -c "ALTER ROLE falcon PASSWORD 'ganti-dengan-password-kuat';"
```

Sesuaikan PostgreSQL dengan RAM 8 GB. Edit `/opt/homebrew/var/postgresql@17/postgresql.conf`:

```ini
shared_buffers = 1GB
effective_cache_size = 3GB
work_mem = 8MB
maintenance_work_mem = 128MB
max_connections = 60
```

Lalu jalankan `sudo brew services restart postgresql@17`.

## 4. Redis

Edit `/opt/homebrew/etc/redis.conf`:

```ini
bind 127.0.0.1
maxmemory 256mb
# Session dan antrean tidak boleh terhapus diam-diam saat memori penuh.
maxmemory-policy noeviction
```

Lalu jalankan `sudo brew services restart redis`.

## 5. Folder Falcon

```bash
sudo mkdir -p /opt/falcon/releases /opt/falcon/shared/tls
sudo chown -R falcon:staff /opt/falcon

mkdir -p /opt/falcon/shared/storage/{framework/cache/data,framework/sessions,framework/views,logs}
ln -s /Volumes/FalconData/app /opt/falcon/shared/storage/app
```

> Jika SSD `FalconData` dienkripsi, centang _Remember password in keychain_ saat pertama kali membukanya, supaya
> SSD otomatis terpasang setelah login FileVault.

## 6. Paket rilis dan pengaturan

Di komputer yang punya internet, dari folder repo Falcon:

```bash
scripts/build-release.sh          # menghasilkan releases/falcon-<versi>.tar.gz dan .sha256
```

Salin kedua file itu ke Mac mini (flashdisk atau jaringan lokal), misalnya ke `~/Downloads`, lalu ekstrak salinan
template dan skrip yang dibutuhkan untuk instalasi pertama:

```bash
cd ~/Downloads
tar -xzf falcon-<versi>.tar.gz falcon-<versi>/.env.example falcon-<versi>/deploy falcon-<versi>/scripts
```

Buat kunci aplikasi **sekali saja**, lalu simpan hasilnya. Kunci ini tidak boleh diganti setelah Falcon dipakai:

```bash
php -r "echo 'base64:'.base64_encode(random_bytes(32)).PHP_EOL;"
```

Salin `.env.example` dari paket rilis menjadi `/opt/falcon/shared/.env`, lalu ubah nilai-nilai ini:

```ini
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:...hasil-perintah-di-atas...
APP_URL=https://falcon.sekolah.lan

DB_DATABASE=falcon
DB_USERNAME=falcon
DB_PASSWORD=ganti-dengan-password-kuat

SESSION_DRIVER=redis
SESSION_SECURE_COOKIE=true
CACHE_STORE=redis
QUEUE_CONNECTION=redis

# Nginx yang mengirim file setelah Falcon mengecek hak akses (hemat worker PHP untuk video besar)
FALCON_X_ACCEL_REDIRECT=/_protected

# Backup harian pukul 02.00 ke disk backup (bukan SSD FalconData)
FALCON_BACKUP_PATH=/Volumes/FalconBackup/falcon
FALCON_BACKUP_KEEP_DAYS=14
```

> Batas unggah bawaan 100 MB per file. Jika `FALCON_UPLOAD_MAX_MB` dinaikkan, naikkan juga `upload_max_filesize`
> dan `post_max_size` di `php-fpm-falcon.conf` serta `client_max_body_size` di `nginx-falcon.conf`.

## 7. PHP-FPM, Nginx, dan HTTPS

```bash
cd ~/Downloads/falcon-<versi>
cp deploy/macos/php-fpm-falcon.conf /opt/homebrew/etc/php/8.4/php-fpm.d/www.conf
cp deploy/macos/php-falcon.ini      /opt/homebrew/etc/php/8.4/conf.d/falcon.ini
cp deploy/macos/nginx-falcon.conf   /opt/homebrew/etc/nginx/servers/falcon.conf
```

**Sertifikat HTTPS:** buat sertifikat untuk `falcon.sekolah.lan` dari CA internal sekolah, lalu simpan sebagai
`/opt/falcon/shared/tls/falcon.crt` dan `falcon.key`. Sertifikat CA tersebut harus dipasang sekali di semua
perangkat sekolah (laptop guru, komputer lab, tablet siswa) supaya browser tidak menampilkan peringatan.
Jika sekolah belum punya CA internal, alat seperti `mkcert` bisa membuatnya secara offline.

```bash
sudo brew services start php@8.4
sudo brew services start nginx
```

## 8. Rilis pertama, akun admin, dan layanan latar belakang

```bash
~/Downloads/falcon-<versi>/scripts/install-release.sh ~/Downloads/falcon-<versi>.tar.gz

cd /opt/falcon/current
php artisan falcon:create-user admin "Administrator" --role=admin
```

Perintah terakhir menampilkan **password sementara** untuk akun admin. Admin wajib menggantinya saat login pertama.

Pasang queue worker dan scheduler sebagai LaunchDaemon. **Keduanya wajib:** tanpa queue worker notifikasi tidak
terkirim, tanpa scheduler backup harian tidak berjalan.

```bash
sudo cp /opt/falcon/current/deploy/macos/launchd/com.falconlms.*.plist /Library/LaunchDaemons/
sudo chown root:wheel /Library/LaunchDaemons/com.falconlms.*.plist
sudo chmod 644 /Library/LaunchDaemons/com.falconlms.*.plist
sudo launchctl bootstrap system /Library/LaunchDaemons/com.falconlms.queue.plist
sudo launchctl bootstrap system /Library/LaunchDaemons/com.falconlms.scheduler.plist
```

## 9. Pemeriksaan setelah instalasi

- `curl -k https://falcon.sekolah.lan/up` menjawab `200`.
- Login sebagai admin dari laptop lain di jaringan sekolah, lalu ganti password sementara.
- `sudo launchctl print system/com.falconlms.queue` menunjukkan `state = running`.
- Uji backup sekali: `cd /opt/falcon/current && php artisan falcon:backup`, lalu pastikan ada file `.dump` di
  `/Volumes/FalconBackup/falcon/database`.
- Buka satu file PDF yang diunggah guru. Jika muncul 404, periksa blok `location /_protected/` di Nginx.
- Di _Activity Monitor → Memory_, grafik _Memory Pressure_ tetap hijau.

## Mengelola akun

Akun dikelola dari halaman **Pengguna** (menu admin): buat akun, ubah peran, reset password, dan impor CSV. Hasil
impor berisi slip password sementara yang bisa langsung dicetak. Password sementara hanya tampil sekali.

Format CSV (pemisah `;` atau `,`, baris pertama judul kolom). Kolom `peran` boleh `siswa`, `guru`, `admin`, atau
`orang tua`; jika kosong dianggap siswa.

```csv
username;nama;peran
0012345678;Budi Santoso;siswa
guru.rina;Rina Wati;guru
```

Jika admin tidak bisa login, akun juga bisa dikelola dari Terminal di Mac mini:

```bash
cd /opt/falcon/current

# Akun baru (peran: admin, teacher, student, guardian). Menampilkan password sementara.
php artisan falcon:create-user 0012345678 "Budi Santoso" --role=student

# Siswa lupa password: buat password sementara baru
php artisan falcon:reset-password 0012345678
```

## Memperbarui Falcon

1. Di komputer yang punya internet: `scripts/build-release.sh`.
2. Salin `falcon-<versi>.tar.gz` dan `.sha256` ke Mac mini.
3. **Di luar jam sekolah dan di luar masa ujian**, jalankan:
    ```bash
    /opt/falcon/current/scripts/install-release.sh ~/Downloads/falcon-<versi>.tar.gz
    ```

Skrip ini memeriksa checksum, menyalakan mode maintenance, menjalankan migrasi database, memindahkan `current` ke
versi baru, me-restart PHP-FPM dan queue worker, lalu menyalakan Falcon kembali. Tiga versi terakhir disimpan.

Jika skrip berhenti karena error, Falcon tetap dalam mode maintenance dengan versi lama. Baca pesan errornya dulu.
Setelah masalahnya jelas, versi lama bisa dinyalakan lagi dengan `php /opt/falcon/current/artisan up`.

## Prosedur setelah listrik padam

UPS menjaga Mac mini tetap menyala saat listrik padam sebentar. Jika padamnya lebih lama dari daya UPS:

1. Setelah listrik kembali, Mac mini menyala sendiri, lalu berhenti di layar FileVault.
2. Petugas IT mengetik password akun `falcon` (bisa memakai keyboard dan monitor, atau _Screen Sharing_ jika sudah diatur).
3. Semua layanan menyala otomatis. Periksa `https://falcon.sekolah.lan/up`.

Tempel prosedur ini di dekat Mac mini dan pastikan lebih dari satu orang tahu password akun `falcon`.

## Cadangan (backup)

Setiap hari pukul 02.00 scheduler menjalankan `php artisan falcon:backup` ke `FALCON_BACKUP_PATH`:

- `database/falcon-<tanggal>-<jam>.dump`: isi database (format `pg_dump` custom). Yang lebih tua dari
  `FALCON_BACKUP_KEEP_DAYS` hari dihapus otomatis.
- `files/`: salinan file unggahan dengan `rsync`. File yang terhapus di Falcon **tidak** ikut terhapus dari backup,
  jadi masih bisa dipulihkan.

Hasilnya tercatat di `storage/logs/laravel.log`. Periksa isi disk backup minimal sebulan sekali, dan **cobalah
memulihkan** ke komputer lain setiap semester, supaya yakin backup benar-benar bisa dipakai.

Memulihkan (di luar jam sekolah):

```bash
cd /opt/falcon/current
php artisan down

# Database: ganti isi database falcon dengan isi file backup
pg_restore --clean --if-exists --no-owner -d falcon /Volumes/FalconBackup/falcon/database/falcon-<tanggal>-<jam>.dump

# File unggahan
rsync -a /Volumes/FalconBackup/falcon/files/ /Volumes/FalconData/app/private/

php artisan up
```

Time Machine boleh tetap dipakai untuk cadangan sistem, tapi **tidak cukup untuk database** yang sedang berjalan.

## Masalah umum

| Gejala                                 | Penyebab yang mungkin                         | Yang dilakukan                                           |
| -------------------------------------- | --------------------------------------------- | -------------------------------------------------------- |
| Browser menampilkan _502 Bad Gateway_  | PHP-FPM tidak berjalan                        | `sudo brew services restart php@8.4`                     |
| Perubahan `.env` tidak berpengaruh     | Pengaturan tersimpan di cache                 | `cd /opt/falcon/current && php artisan optimize`         |
| Peringatan sertifikat di browser       | Sertifikat CA belum dipasang di perangkat itu | Pasang sertifikat CA sekolah di perangkat tersebut       |
| Halaman "Sedang dalam perawatan" terus | Pembaruan berhenti di tengah jalan            | Lihat bagian [Memperbarui Falcon](#memperbarui-falcon)   |
| Lonceng notifikasi tidak pernah berisi | Queue worker tidak berjalan                   | `sudo launchctl kickstart -k system/com.falconlms.queue` |
| Unggahan gagal dengan pesan _413_      | File lebih besar dari batas Nginx/PHP         | Lihat catatan batas unggah di langkah 6                  |
