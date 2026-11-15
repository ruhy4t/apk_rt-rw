# apk_rt-rw - Portal Warga RT/RW

Portal warga berbasis Laravel dengan halaman publik dan area privat. Database aplikasi dirancang untuk MySQL/TiDB Cloud; kredensial database dan object storage hanya boleh disimpan sebagai environment variables sisi server.

## Status

MVP saat ini mencakup portal publik; login akun yang dibuat pengurus; pengelolaan wilayah, akun, warga, pengumuman, agenda, transaksi/iuran, inventaris/peminjaman, dan permintaan layanan. Upload lampiran PDF/JPG/PNG hingga 5 MB dan unduh melalui streaming server tersedia untuk permintaan layanan, tetapi dinonaktifkan secara default sampai B2 privat dikonfigurasi dan lokasi data ditinjau. Belum ada koneksi TiDB produksi, konfigurasi Vercel, maupun deployment.

## Pengembangan lokal

1. Buka Laragon, pilih **Start All**, lalu buka **Terminal** dari Laragon dan masuk ke folder project.
2. Salin `.env.example` menjadi `.env` dengan `Copy-Item .env.example .env`.
3. Konfigurasi bawaan `.env.example` memakai SQLite lokal untuk melihat UI tanpa kredensial cloud. Jalankan `composer install`, `npm install`, `php artisan key:generate`, `php artisan migrate`, dan `npm run build`.
4. Buat akun administrator lokal dengan `php artisan app:create-admin admin "Administrator RT RW"`. Kata sandi diminta secara interaktif.
5. Pastikan virtual host Apache mengarah ke folder `D:\laragon\www\aplikasi-rt\public` (bukan root project), lalu buka **http://aplikasi-rt.test**. Restart Apache setelah mengubah konfigurasi.

SQLite tersebut hanya untuk preview lokal, bukan database production. Login administrator tersedia setelah perintah `app:create-admin`; halaman publik bisa dibuka tanpa login. Laragon dapat membuat domain `.test` otomatis dari nama folder project.

Pengujian menggunakan SQLite in-memory: `php artisan test`.

## TiDB Cloud

1. Buat cluster TiDB Cloud untuk development/staging dan pilih region setelah membandingkan lokasi Vercel dengan latensi ke Indonesia.
2. Dari halaman **Connect**, salin endpoint, port, username persis seperti yang diberikan (Starter dapat memakai username ber-prefix), dan konfigurasi TLS. Batasi IP Access List ke IP yang diperlukan; jangan membuka akses ke semua alamat.
3. Buat database aplikasi melalui SQL Editor: `CREATE DATABASE IF NOT EXISTS apk_rt_rw CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;`.
4. Di `.env` lokal, ubah `DB_CONNECTION=mysql` dan isi `DB_HOST`, `DB_PORT=4000`, `DB_DATABASE=apk_rt_rw`, `DB_USERNAME`, `DB_PASSWORD`, serta `MYSQL_ATTR_SSL_CA` jika diminta konfigurasi TiDB. Jangan kirim password ke chat atau commit.
5. Jalankan `php artisan migrate` hanya pada cluster development/staging terlebih dahulu. Migration Laravel adalah sumber skema tabel; jangan membuat tabel yang sama manual di phpMyAdmin. Verifikasi query dan migration pada versi TiDB yang akan dipakai sebelum production.
6. Di Vercel, atur variabel yang sama pada environment yang tepat (Preview/Production terpisah). Jangan deploy production atau membuka database production sebelum runtime Laravel, konektivitas TLS, egress/IP allowlist, dan migration diuji serta disetujui.

Panduan resmi: [buat cluster](https://docs.pingcap.com/tidbcloud/dev-guide-build-cluster-in-cloud/) dan [koneksi Starter/Essential](https://docs.pingcap.com/tidbcloud/connect-to-tidb-cluster-serverless/).

## Berkas privat

1. Buat bucket B2 dengan akses **Private**; catat S3 Endpoint dan region bucket.
2. Buat Application Key baru yang dibatasi ke bucket itu, bukan Master Key. Backblaze mencatat bahwa key terbatas-bucket untuk S3 API memerlukan `listAllBucketNames`; berikan hanya permission yang dibutuhkan oleh integrasi dan verifikasi melalui uji staging.
3. Isi `B2_APPLICATION_KEY_ID`, `B2_APPLICATION_KEY`, `B2_BUCKET`, `B2_REGION`, dan `B2_ENDPOINT` (`https://s3.<region>.backblazeb2.com`) di `.env` lokal atau environment variables Vercel. Jangan commit nilainya.
4. Biarkan `B2_PRIVATE_FILES_ENABLED=false` sampai koneksi, kebijakan akses, dan uji upload/download berhasil di staging.

Backblaze saat ini tidak mencantumkan region penyimpanan di Asia. Karena itu, sebelum dokumen warga disimpan di sana, tinjau transfer/lokasi data terhadap UU PDP dan ukur dampak latensi/biayanya. Jangan unggah data warga nyata untuk uji awal. Unduhan dilayani oleh streaming aplikasi setelah otorisasi; semua transfer melewati runtime Vercel sehingga perlu diuji batas ukuran dan biayanya.

Panduan resmi: [S3 Compatible API Backblaze](https://help.backblaze.com/hc/en-us/articles/360047425453-Getting-Started-with-the-S3-Compatible-API) dan [region data center](https://www.backblaze.com/docs/cloud-storage-data-regions).

## Keamanan dan deployment

- Tidak ada pendaftaran mandiri.
- Akun nonaktif ditolak saat login dan dikeluarkan dari sesi saat mengakses area privat.
- Sesi dienkripsi dan cookie memakai HttpOnly serta SameSite dari konfigurasi Laravel.
- Atur `SESSION_SECURE_COOKIE=true` pada environment HTTPS production; jangan aktifkan `remember me` pada perangkat bersama.
- Tidak ada migration yang dijalankan pada TiDB produksi dan tidak ada deployment yang dilakukan.
- Akun dibuat administrator; role petugas/warga dan cakupan wilayah diperiksa pada server untuk setiap operasi privat.
- Data keuangan dan inventaris publik hanya ditampilkan sebagai agregat yang ditandai pengurus; rincian transaksi, alamat, nomor telepon, dan permintaan layanan tetap privat.
- Audit perubahan mencatat aktor, jenis aksi, dan ID objek saja; isi pribadi, kata sandi, dan nilai transaksi tidak disalin ke audit log.
- Sebelum menghubungkan Vercel/GitHub, verifikasi runtime PHP, konfigurasi build, penanganan sesi/cache/berkas pada lingkungan serverless, region database, dan TLS.
