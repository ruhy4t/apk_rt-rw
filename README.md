# apk_rt-rw - Portal Warga RT/RW

Portal warga berbasis Laravel dengan halaman publik dan area privat. Kredensial database dan penyimpanan berkas hanya boleh disimpan sebagai environment variables sisi server.

## Status

MVP saat ini mencakup portal publik; login akun yang dibuat pengurus; pengelolaan wilayah, akun, warga, pengumuman, agenda, transaksi/iuran, inventaris/peminjaman, dan permintaan layanan. Upload lampiran PDF/JPG/PNG hingga 5 MB secara lokal dan 1 MB pada container Vercel serta unduh melalui streaming server tersedia untuk permintaan layanan, tetapi penyimpanan berkas privat dinonaktifkan secara default. Konfigurasi container Vercel disiapkan, tetapi belum diuji melalui deployment dan belum terhubung ke database production.

## Pengembangan lokal

Installer browser di bawah ini hanya untuk Laragon lokal dengan aplikasi dan dependency yang sudah tersedia, `.env` yang sudah mempunyai `APP_KEY`, serta Apache yang mengarah ke folder `public`. Installer tidak mengatur deployment, tidak membuat database, dan tidak memindahkan data SQLite lama.

1. Jalankan Apache dan MySQL di Laragon. Di phpMyAdmin atau HeidiSQL, buat database MySQL baru yang kosong. Siapkan user MySQL dengan izin hanya pada database tersebut, termasuk izin untuk membuat dan mengubah tabel.
2. Buat file `storage/app/private/install-token` di luar folder `public` menggunakan editor teks. Isinya harus berupa kode acak rahasia minimal 32 karakter; jangan bagikan atau simpan di Git. Pastikan namanya tidak berakhiran `.txt`.
3. Buka alamat lokal aplikasi di browser pada komputer yang sama, lalu tambahkan `/install`. Isi kode tersebut, nama database, kredensial MySQL lokal, dan akun admin awal. Installer hanya menghubungi MySQL pada `127.0.0.1:3306` dan menolak database yang berisi tabel atau view.
4. Setelah berhasil, masuk dengan akun admin. Installer terkunci dan file kode dihapus. Cadangan konfigurasi lama disimpan di `.env.backup`; file ini sensitif dan tidak boleh dibagikan atau di-commit.

Jika pemasangan gagal, jangan menghapus tabel atau membuka kunci pemasangan sembarangan. Catat pesan umum tanpa password, periksa database dan `.env.backup`, lalu minta bantuan sebelum mencoba lagi. Database SQLite lama tetap ada dan tidak otomatis dipindahkan. Installer tidak menyediakan pemulihan akun dan tidak boleh digunakan untuk production.

Apache harus menolak akses ke file project di luar `public`, termasuk melalui virtual host default; aturan `.htaccess` disertakan untuk Apache. Pastikan konfigurasi server mengizinkan aturan tersebut dan uji perlindungannya sebelum menaruh kredensial di lokal.

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
- Pembuatan tabel, akun admin pertama, pengaturan secret, serta deployment production memerlukan persetujuan dan verifikasi tersendiri. Installer browser hanya untuk lokal.
