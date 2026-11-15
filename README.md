# apk_rt-rw - Portal Warga RT/RW

Portal warga berbasis Laravel dengan halaman publik dan area privat. Kredensial database dan penyimpanan berkas hanya boleh disimpan sebagai environment variables sisi server.

## Status

MVP saat ini mencakup portal publik; login akun yang dibuat pengurus; pengelolaan wilayah, akun, warga, pengumuman, agenda, transaksi/iuran, inventaris/peminjaman, dan permintaan layanan. Upload lampiran PDF/JPG/PNG hingga 5 MB dan unduh melalui streaming server tersedia untuk permintaan layanan, tetapi penyimpanan berkas privat dinonaktifkan secara default. Belum ada koneksi database production, konfigurasi Vercel, maupun deployment.

## Pengembangan lokal

1. Buka Laragon, pilih **Start All**, lalu buka **Terminal** dari Laragon dan masuk ke folder project.
2. Salin `.env.example` menjadi `.env` dengan `Copy-Item .env.example .env`.
3. Konfigurasi bawaan `.env.example` memakai SQLite lokal untuk melihat UI tanpa kredensial cloud. Jalankan `composer install`, `npm install`, `php artisan key:generate`, `php artisan migrate`, dan `npm run build`.
4. Buat akun administrator lokal dengan `php artisan app:create-admin admin "Administrator RT RW"`. Kata sandi diminta secara interaktif.
5. Jalankan `php artisan serve --host=127.0.0.1 --port=8000`, lalu buka **http://127.0.0.1:8000**. Hentikan server dengan `Ctrl+C`.

SQLite tersebut hanya untuk preview lokal, bukan database production. Login administrator tersedia setelah perintah `app:create-admin`; halaman publik bisa dibuka tanpa login. Konfigurasi Laragon Apache/Nginx opsional, tetapi document root harus menunjuk ke folder `public`.

Pengujian menggunakan SQLite in-memory: `php artisan test`.

## Keamanan dan deployment

- Tidak ada pendaftaran mandiri.
- Akun nonaktif ditolak saat login dan dikeluarkan dari sesi saat mengakses area privat.
- Sesi dienkripsi dan cookie memakai HttpOnly serta SameSite dari konfigurasi Laravel.
- Atur `SESSION_SECURE_COOKIE=true` pada environment HTTPS production; jangan aktifkan `remember me` pada perangkat bersama.
- Tidak ada migration yang dijalankan pada database production dan tidak ada deployment yang dilakukan.
- Akun dibuat administrator; role petugas/warga dan cakupan wilayah diperiksa pada server untuk setiap operasi privat.
- Data keuangan dan inventaris publik hanya ditampilkan sebagai agregat yang ditandai pengurus; rincian transaksi, alamat, nomor telepon, dan permintaan layanan tetap privat.
- Audit perubahan mencatat aktor, jenis aksi, dan ID objek saja; isi pribadi, kata sandi, dan nilai transaksi tidak disalin ke audit log.
- Sebelum menghubungkan Vercel/GitHub, verifikasi runtime PHP, konfigurasi build, penanganan sesi/cache/berkas pada lingkungan serverless, region database, dan TLS.
