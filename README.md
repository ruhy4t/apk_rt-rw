# apk_rt-rw - Portal Warga RT/RW

Portal warga berbasis Laravel dengan halaman publik dan area privat. Kredensial database dan penyimpanan berkas hanya boleh disimpan sebagai environment variables sisi server.

## Status

MVP saat ini mencakup portal publik; login akun yang dibuat pengurus; pengelolaan wilayah, akun, warga, pengumuman, agenda, transaksi/iuran, inventaris/peminjaman, dan permintaan layanan. Upload lampiran PDF/JPG/PNG hingga 5 MB secara lokal dan 1 MB pada container Vercel serta unduh melalui streaming server tersedia untuk permintaan layanan, tetapi penyimpanan berkas privat dinonaktifkan secara default. Konfigurasi container Vercel disiapkan, tetapi belum diuji melalui deployment dan belum terhubung ke database production.

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
- Konfigurasi Vercel menggunakan container PHP; hanya direktori `public` yang disajikan. `.dockerignore` membatasi file yang masuk ke image dan tidak memasukkan `.env`, storage lokal, atau database lokal.
- Container menyetel batas lampiran `PRIVATE_FILE_MAX_KB=1024`; Laragon tetap memakai default 5120 KB. Jangan menaikkan batas container tanpa memastikan batas request Vercel.
- Container Vercel mempercayai hanya header proxy alamat pengunjung dan protokol; jangan gunakan konfigurasi ini pada server yang dapat diakses langsung tanpa proxy tepercaya.
- Sesi dan cache perlu penyimpanan bersama yang sesuai; filesystem container tidak permanen. Jangan gunakan contoh driver sesi `array` untuk login production. Kredensial, `APP_KEY`, dan pengaturan lingkungan harus dipisahkan antara Preview dan Production.
- Pembuatan tabel, akun admin pertama, pengaturan secret, serta deployment production memerlukan persetujuan dan verifikasi tersendiri.
