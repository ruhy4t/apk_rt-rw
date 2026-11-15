# GitHub Copilot Instructions — APK-RT-RW

Baca dan patuhi `AGENTS.md` di root repository sebelum memberi saran, membuat rencana, atau mengubah kode. Instruksi ini berlaku untuk seluruh repository.

## Konteks proyek dan keputusan teknologi

APK-RT-RW adalah aplikasi informasi dan layanan warga tingkat RT/RW.

Keputusan yang sudah ditetapkan:
- Hosting/deployment: **Vercel**.
- Database: **TiDB Cloud**.
- Frontend: **belum ditentukan**.
- Backend: **belum ditentukan**.
- Autentikasi/provider login: **belum ditentukan**.
- Google Apps Script bukan platform backend proyek.
- Jangan menambahkan Firebase atau mengganti database/hosting tanpa persetujuan eksplisit.

Jangan mengasumsikan Vercel berarti harus memakai Next.js. Jangan memilih framework frontend, backend, ORM, atau provider autentikasi sebelum pemilik menetapkan atau menyetujui rekomendasi.

Kebutuhan produk yang telah dibicarakan:
- Portal informasi publik dapat diakses tanpa login.
- Layanan/data khusus warga memerlukan autentikasi dan otorisasi.
- Pengumuman dapat bersifat publik atau khusus warga.

Prioritas: **KEAMANAN, KECEPATAN AKSES dari Indonesia, privasi warga, keandalan, dan ketepatan terhadap instruksi pengguna.**

## Fokus dan disiplin perubahan

1. Pahami struktur, dokumentasi, pola kode, konfigurasi, dan file terkait sebelum mengedit.
2. Kerjakan hanya hal yang diminta. Jangan menambah fitur, redesign, refactor luas, atau mengubah alur bisnis atas inisiatif sendiri.
3. Jangan menebak kebutuhan yang ambigu. Ajukan pertanyaan jika keputusan memengaruhi data, keamanan, arsitektur, biaya, atau perilaku pengguna.
4. Pilih perubahan sekecil mungkin yang menyelesaikan permintaan.
5. Periksa `git status` dan diff. Jangan menimpa atau membatalkan perubahan lokal pengguna.
6. Pertahankan kompatibilitas fitur, API, dan data yang sudah ada.
7. Pisahkan usulan dari perubahan yang benar-benar dilakukan. Jangan mengklaim test atau deployment berhasil tanpa bukti.

## Persetujuan wajib

Minta persetujuan eksplisit sebelum:
- menghapus, memindahkan, mengganti nama, atau merombak banyak file;
- memilih/mengganti frontend, backend, framework, ORM, auth provider, atau arsitektur;
- mengubah skema TiDB, migrasi, relasi, indeks penting, atau data;
- melakukan reset, drop, truncate, penghapusan massal, atau migrasi produksi;
- mengubah login, session, role, permission, atau kebijakan akses;
- mengubah secret, environment variables sensitif, OAuth, domain, DNS, permission, atau deployment;
- menambahkan layanan berbayar, vendor eksternal, atau dependency utama;
- melakukan refactor luas, redesign, perubahan navigasi/alur bisnis, atau deploy production.

Saat meminta persetujuan, jelaskan perubahan, alasan, file/tabel/layanan terdampak, manfaat, risiko, biaya, rencana pengujian, dan rollback. Tunggu persetujuan; jangan menganggap instruksi umum sebagai izin.

## Keamanan wajib

- Terapkan secure-by-default, least privilege, dan deny-by-default.
- Autentikasi final belum diputuskan; jangan mengintegrasikan provider tanpa persetujuan.
- Lakukan otorisasi di server pada setiap operasi baca/tulis/hapus/ekspor/unduh.
- Jangan percaya role, email, user ID, RT ID, atau RW ID dari client sebelum diverifikasi server.
- Pastikan pembatasan data RT/RW berlaku pada query dan operasi server, bukan hanya tampilan.
- Cegah IDOR/BOLA dengan pemeriksaan hak akses terhadap setiap objek.
- Data pribadi warga tidak boleh terekspos di halaman publik, URL, log, analytics, error, atau cache bersama.
- Validasi input di server, gunakan parameterized SQL, encode output, dan cegah XSS/injection.
- Jangan menyimpan secret, token, password, atau connection string di source code, repository, browser bundle, atau source map publik.
- Jangan mengandalkan penyembunyian tombol/menu sebagai kontrol keamanan.
- Amankan file upload dan akses lampiran.
- Gunakan data sintetis untuk pengujian; jangan memakai data warga nyata tanpa izin dan perlindungan yang sesuai.

## TiDB Cloud

- TiDB Cloud adalah database proyek. Jangan menggantinya tanpa persetujuan.
- Akses database hanya dari lingkungan server; tidak boleh langsung dari browser.
- Simpan kredensial di environment variables/secret manager Vercel dengan scope yang tepat.
- Jangan pernah menampilkan nilai secret dalam log, output debug, dokumentasi, atau commit.
- Gunakan database user dengan hak minimum, TLS, dan kontrol jaringan yang sesuai.
- Gunakan prepared statements/parameterized queries; jangan menggabungkan input pengguna ke SQL.
- Periksa transaksi untuk operasi multi-langkah dan integritas data.
- Pertimbangkan connection pooling, batas koneksi, cold start, dan pola eksekusi serverless sebelum memilih driver.
- Verifikasi kompatibilitas driver/ORM dan fitur SQL dengan TiDB Cloud yang digunakan; jangan mengasumsikan kompatibilitas penuh.
- Jangan menonaktifkan verifikasi sertifikat TLS untuk mengatasi masalah koneksi.
- Jangan menjalankan DDL, migrasi, seed, reset, atau perubahan data produksi tanpa persetujuan.

## Vercel

- Vercel adalah platform hosting/deployment; framework aplikasi belum ditentukan.
- Pisahkan konfigurasi Development, Preview, dan Production.
- Jangan mengekspos secret ke browser atau variabel publik framework.
- Jangan mengubah domain, DNS, build settings, deployment protection, atau konfigurasi production tanpa persetujuan.
- Jangan melakukan production deployment tanpa persetujuan eksplisit.
- Periksa log build/runtime agar tidak membocorkan token, data pribadi, connection string, atau detail internal.
- Evaluasi lokasi region Vercel dan TiDB Cloud bersama-sama untuk mengurangi latensi aplikasi-database; ukur sebelum menyimpulkan konfigurasi terbaik.

## Kecepatan akses dari Indonesia

- Utamakan mobile-first, halaman awal ringan, dan jaringan seluler yang bervariasi.
- Minimalkan JavaScript, CSS, font, gambar, dependency, dan request pihak ketiga.
- Gunakan gambar terkompresi/responsif dan lazy loading bila sesuai.
- Gunakan pagination, filter, dan pencarian server-side; jangan mengirim seluruh data warga sekaligus.
- Optimalkan query TiDB berdasarkan pola penggunaan dan pengukuran; hindari N+1 query dan SELECT kolom yang tidak diperlukan.
- Gunakan indeks berdasarkan query yang nyata; ingat indeks menambah biaya penyimpanan dan beban tulis.
- Kurangi round-trip browser-server-database tanpa membuat response terlalu besar.
- Cache hanya data yang aman; pisahkan cache publik dan personal serta atur TTL/invalidation.
- Ukur LCP, INP, CLS, TTFB, ukuran transfer, jumlah request, API latency, database latency, dan error rate.
- Laporkan lokasi, perangkat, jaringan, cache, dan environment pengukuran.
- Jangan mengklaim aplikasi cepat di Indonesia tanpa hasil ukur yang dapat diulang.
- Jangan mengorbankan keamanan demi skor performa.

## Kualitas implementasi

- Ikuti struktur, gaya, dan konvensi kode yang sudah ada.
- Jangan menambah dependency tanpa alasan; periksa ukuran, lisensi, pemeliharaan, kerentanan, dan alternatif.
- Jangan melakukan upgrade mayor atau mengganti package manager tanpa persetujuan.
- Jangan menambahkan GAS atau Firebase sebagai jalan pintas.
- Jangan membuat abstraksi berlebihan atau refactor yang tidak diminta.
- Untuk perubahan UI, pertahankan desain yang ada kecuali pengguna meminta perubahan.

## Verifikasi sebelum menyatakan selesai

- Periksa diff dan pastikan hanya file relevan yang berubah.
- Jalankan formatter, linter, type check, test, dan build yang tersedia serta relevan.
- Uji validasi input, akses tanpa login, role yang tidak sesuai, akses lintas RT/RW, dan akses objek yang tidak dimiliki jika relevan.
- Periksa response, URL, log, cache, dan halaman publik dari kebocoran data.
- Untuk perubahan database, uji query dan migrasi di lingkungan non-production terlebih dahulu.
- Untuk UI, periksa mobile dan desktop.
- Laporkan test yang tidak dapat dijalankan beserta alasannya.
- Jangan menyebut aman, cepat, bebas bug, atau siap production tanpa verifikasi yang memadai.

## Laporan pekerjaan

Setiap penyelesaian harus menyebut:
- permintaan dan ruang lingkup;
- analisis/temuan awal;
- file yang diubah dan alasan;
- dampak keamanan dan risiko tersisa;
- dampak performa Indonesia dan hasil pengukuran jika tersedia;
- test/perintah yang dijalankan beserta hasil aktual;
- hal yang belum dilakukan atau menunggu persetujuan.

**Aturan utama: fokus pada instruksi pengguna, jangan asal edit, jangan menetapkan teknologi yang belum diputuskan, jangan melakukan perubahan berisiko tanpa persetujuan, dan selalu utamakan KEAMANAN serta KECEPATAN AKSES dari Indonesia.**
