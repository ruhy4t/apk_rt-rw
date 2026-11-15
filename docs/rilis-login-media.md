# Rilis pengaman login dan media

TiDB Cloud adalah database produksi. MySQL Laragon adalah database lokal. File SQL di `docs/sql` tidak berisi koneksi, kredensial, data warga, atau penggantian kata sandi. Agen tidak menjalankan SQL produksi.

## Login

CAPTCHA hitungan sederhana diperiksa di server, berlaku terbatas, dan sekali pakai. CAPTCHA ini tidak memakai layanan pihak ketiga. Kesalahan kata sandi dihitung berdasarkan nama pengguna yang dinormalisasi, melalui kunci HMAC pada penyimpanan cache database bersama. Lima kesalahan mengunci percobaan akun tersebut selama lima menit; mengganti IP atau membuka sesi baru tidak menghapus statusnya. Login yang berhasil menghapus hitungan kegagalan.

Tidak ada kolom baru pada `users` dan tidak ada SQL login tambahan. Aplikasi menggunakan tabel `cache` dan `cache_locks` yang sudah dibuat migrasi dasar `0001_01_01_000001_create_cache_table.php`. Tabel tersebut harus tersedia pada koneksi cache database aplikasi; penyimpanan file atau memori proses tidak dapat menggantikan status kunci bersama. Jangan menghapus cache database untuk membuka kunci akun karena tindakan itu juga menghapus status pengaman login. Tunggu lima menit.

CAPTCHA sederhana membantu menghambat percobaan otomatis dasar. Ia tidak menjamin bahwa penyerang tidak dapat memecahkan tantangannya. Penguncian juga dapat dipicu oleh orang lain yang mengetahui username; pesan kesalahan tidak boleh mengungkap apakah akun tersebut terdaftar.

## Foto dan galeri

Foto pengumuman dan agenda memakai metadata `stored_files` yang sudah ada, dengan objek privat pada penyimpanan B2 yang telah didukung aplikasi. Tidak ada penyimpanan foto permanen pada filesystem Vercel. Nama objek tidak menjadi izin akses: setiap permintaan gambar diperiksa terhadap publikasi, audiens, dan wilayah induknya.

Galeri memerlukan tabel tambahan `gallery_items`. Struktur disediakan dalam `docs/sql/04-gallery-tidb.sql` untuk TiDB dan `docs/sql/04-gallery-mysql8.sql` untuk MySQL 8. Pilih satu dialek sesuai lingkungan, jangan mengimpor kedua berkas ke database yang sama. Sebelum tabel tersedia, konten lama tetap dapat ditampilkan dan galeri memberi status belum tersedia.

Versi TiDB aktual perlu diperiksa: foreign key didukung mulai v6.6.0 dan dinyatakan generally available mulai v8.5.0. Pastikan pemeriksaan foreign key aktif; jangan menonaktifkannya untuk melewati error impor. [Dokumentasi resmi TiDB](https://docs.pingcap.com/tidb/stable/foreign-key/).

Video tetap disimpan sebagai tautan HTTPS di kolom `video_url`, lalu ditampilkan melalui iframe embed YouTube, Instagram, TikTok, atau Vimeo. Perubahan dari tautan ke embed tidak menambah tabel/kolom baru. Server membangun alamat pemutar dari host dan ID video yang divalidasi; tidak menerima kode HTML/iframe pengguna, mengambil metadata melalui jaringan, atau mengunduh video. Iframe memakai lazy loading, sandbox, referrer origin saja, dan tanpa autoplay. Browser menghubungi platform asal ketika pemutar dimuat; ini menambah request pihak ketiga dan dapat memakai cookie platform tersebut.

Gunakan tautan video lengkap. Tautan pendek TikTok tidak diikuti server; buka dahulu di browser lalu salin alamat lengkap video. Tautan lama dari platform yang belum didukung tetap dapat dibuka sebagai fallback, tetapi tautan baru harus memiliki format embed yang dikenali. Video privat, dihapus, dibatasi usia/wilayah, atau tidak mengizinkan embed dapat ditolak oleh platform. Tautan asal tetap tersedia di bawah pemutar. Jangan memasukkan token, tautan akses privat, identitas warga, atau kredensial ke URL video.

Referensi: [pemutar YouTube](https://developers.google.com/youtube/player_parameters), [identitas Referer YouTube](https://developers.google.com/youtube/terms/required-minimum-functionality#embedded-player-api-client-identity), [pemutar TikTok](https://developers.tiktok.com/docs/en/embed-player), [embed Instagram](https://developers.facebook.com/docs/instagram-platform/embed-button). Halaman resmi Meta dibatasi HTTP 429 saat pemeriksaan; pemutaran Instagram pada browser/perangkat nyata belum diverifikasi. Penyematan dapat bergantung pada pengaturan privasi pemilik konten dan kebijakan platform terbaru.

Unggahan foto memerlukan konfigurasi B2 privat yang sudah aktif. Perubahan ini tidak membuat bucket, mengganti secret, menyalakan layanan berbayar baru, atau mengubah pengaturan Vercel. Jika penyimpanan belum aktif, unggahan memberi informasi yang jelas; menaruh foto pada direktori lokal container bukan penggantinya.

## Urutan penerapan dan pemeriksaan

1. Pastikan backup database dapat dipulihkan dan uji SQL galeri dua kali pada salinan non-production dengan engine/versi yang sesuai. Jalankan `docs/sql/00-preflight.sql`, lalu berkas `04-gallery` untuk dialek tujuan. Hentikan pada error pertama. Tidak ada penghapusan atau pemindahan data lama.
2. Periksa kolom, indeks, dan foreign key pada query postflight. `CREATE TABLE IF NOT EXISTS` hanya melewati tabel yang namanya sudah ada; ia tidak memperbaiki definisi tabel yang tidak sesuai.
3. Terapkan kode aplikasi yang telah diuji. Tidak ada migrasi otomatis melalui proses build/deploy Vercel. SQL manual tidak mencatat riwayat `migrations`; rekonsiliasikan sebelum menjalankan migrasi menyeluruh. Migrasi galeri sendiri memiliki pemeriksaan keberadaan tabel.
4. Periksa akses tamu, warga, petugas RT lain, draf, dan audiens khusus warga. Periksa satu unggahan foto sintetis dan tautan video yang tidak valid. Jangan memakai data warga nyata untuk tes.
5. Uji lima login salah, penolakan login selama masa kunci meski password benar, berakhirnya kunci, CAPTCHA salah/kedaluwarsa/pakai ulang, serta login benar. Jangan menampilkan nilai password atau secret dalam log.

Untuk rollback kode, pertahankan tabel dan objek privat; jangan menjalankan `DROP` atau menghapus foto massal. Penurunan versi ke kode tanpa pengaman login menghilangkan perilaku pembatasan baru, sehingga perlu keputusan pemilik. Bila fitur enkripsi tambahan telah diaktifkan, rollback harus mengikuti `docs/enkripsi-data-pribadi.md`; SQL galeri tidak melakukan enkripsi maupun konversi data pribadi.

Pengujian otomatis menggunakan database sementara dan penyimpanan palsu tidak membuktikan konektivitas TiDB/B2 produksi atau tampilan di perangkat nyata. Latensi Indonesia, batas akun B2, dan status deployment harus diverifikasi terpisah. Tidak ada perubahan region, domain, DNS, environment sensitif, atau konfigurasi production dalam panduan ini.
