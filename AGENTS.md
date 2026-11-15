# AGENTS.md — Pedoman Utama Agen AI untuk APK-RT-RW

Dokumen ini merupakan aturan kerja utama bagi Codex, GitHub Copilot, dan seluruh AI coding agent yang bekerja pada repository APK-RT-RW. Baca dokumen ini sebelum menganalisis, memberi saran, atau mengubah apa pun.

## 1. Identitas dan arah proyek

APK-RT-RW adalah aplikasi untuk mendukung informasi dan layanan warga pada tingkat RT/RW.

Keputusan proyek yang sudah ditetapkan pemilik:

- **Hosting/deployment:** Vercel.
- **Database:** TiDB Cloud.
- **Frontend:** belum ditentukan.
- **Backend:** belum ditentukan.
- **Autentikasi:** belum ditetapkan secara teknis; jangan mengasumsikan provider atau metode login final.
- **Firebase:** bukan pilihan yang telah disetujui. Jangan menambahkan Firebase atau layanan sejenis tanpa persetujuan eksplisit.
- **Google Apps Script:** bukan platform backend proyek. Jangan membangun fitur menggunakan GAS kecuali pemilik proyek secara eksplisit mengubah keputusan ini.

Gambaran kebutuhan produk yang telah dibicarakan:

- Portal informasi publik dapat diakses tanpa login.
- Layanan atau data khusus warga memerlukan autentikasi dan otorisasi.
- Pengumuman dapat memiliki cakupan publik dan khusus warga.

Kebutuhan produk tersebut bukan izin untuk menetapkan sendiri detail implementasi. Jika kebutuhan atau alur belum jelas, minta klarifikasi.

**Prioritas utama proyek: KEAMANAN, KECEPATAN AKSES dari Indonesia, perlindungan data warga, keandalan, dan ketepatan terhadap instruksi pemilik.**

## 2. Aturan keputusan teknologi

1. Jangan memilih framework frontend, bahasa/backend framework, ORM, auth provider, atau layanan tambahan sebelum pemilik menetapkannya atau menyetujui rekomendasi.
2. Jangan menganggap Vercel berarti proyek harus menggunakan Next.js. Vercel adalah keputusan hosting/deployment; framework tetap belum ditentukan.
3. Jangan menganggap TiDB Cloud berarti boleh mengakses database langsung dari browser. Akses database wajib melalui komponen server yang aman.
4. Jangan mengubah TiDB Cloud ke Firebase, Supabase, PlanetScale, MySQL provider lain, atau database lain tanpa persetujuan eksplisit.
5. Jangan menambah layanan berbayar, vendor eksternal, atau ketergantungan baru tanpa menjelaskan manfaat, biaya, risiko, dan meminta persetujuan.
6. Sebelum merekomendasikan stack, analisis kebutuhan, struktur repository, batasan Vercel, kompatibilitas TiDB Cloud, keamanan, biaya operasional, dan latensi dari Indonesia. Sajikan pilihan beserta trade-off; jangan langsung memasang atau memigrasikan teknologi.
7. Jika repository sudah berisi teknologi atau pola implementasi, jangan menggantinya hanya karena preferensi pribadi agen. Laporkan kondisi aktual dan minta persetujuan untuk perubahan arsitektur.

## 3. Prinsip kerja wajib

1. **Pahami sebelum mengubah.** Periksa struktur repository, dokumentasi, pola kode, konfigurasi, dependensi, skema database, dan alur aplikasi yang relevan.
2. **Fokus pada permintaan.** Kerjakan hanya ruang lingkup yang diminta. Jangan menambah fitur, merombak UI, mengganti library, atau melakukan refactor yang tidak diminta.
3. **Jangan mengarang kebutuhan.** Jika instruksi ambigu dan berpengaruh pada data, keamanan, arsitektur, biaya, tampilan utama, atau perilaku pengguna, berhenti dan ajukan pertanyaan yang spesifik.
4. **Perubahan minimal.** Pilih solusi paling sederhana yang menyelesaikan kebutuhan secara lengkap, aman, dan dapat diuji.
5. **Jaga pekerjaan pengguna.** Periksa `git status` dan diff sebelum bekerja. Jangan menimpa, menghapus, atau membatalkan perubahan lokal pengguna yang belum disimpan/di-commit.
6. **Pertahankan kompatibilitas.** Jangan menghapus fitur atau mengubah kontrak API/data yang masih digunakan tanpa analisis dampak dan persetujuan.
7. **Jujur tentang hasil.** Bedakan analisis, usulan, perubahan lokal, pengujian, dan deployment. Jangan mengklaim sesuatu telah berhasil jika belum diverifikasi.

## 4. Persetujuan sebelum perubahan

### Perubahan kecil yang umumnya boleh dilakukan

Jika ruang lingkupnya jelas dan risikonya rendah, agen boleh:
- membaca dan menganalisis kode;
- memperbaiki bug atau bagian kecil yang secara eksplisit diminta;
- memperbaiki validasi atau tampilan yang diminta tanpa mengubah alur lain;
- menambah atau memperbarui test yang relevan;
- memperbaiki dokumentasi sesuai permintaan.

### Wajib meminta persetujuan eksplisit

Jangan melakukan hal berikut sebelum pemilik menyetujui rencana:

- menghapus, memindahkan, mengganti nama, atau merombak banyak file/modul;
- mengubah framework, arsitektur, bahasa, backend, ORM, auth provider, atau layanan infrastruktur;
- mengubah skema TiDB, tipe/relasi/indeks penting, migrasi, atau format data tersimpan;
- menghapus, mengubah massal, mengimpor, atau memigrasikan data;
- mengubah autentikasi, otorisasi, role, session, kebijakan akses, atau alur pemulihan akun;
- mengubah secret, environment variables sensitif, konfigurasi OAuth, domain, DNS, permission, atau pengaturan deployment;
- menambahkan layanan berbayar, vendor eksternal, atau dependency utama;
- melakukan refactor luas, redesign, perubahan navigasi, atau perubahan alur bisnis di luar permintaan;
- menjalankan reset database, migrasi destruktif, penghapusan resource, atau operasi yang sulit dibatalkan;
- melakukan deployment ke production atau tindakan eksternal yang berdampak nyata.

Saat meminta persetujuan, jelaskan secara ringkas:
1. perubahan yang diusulkan dan alasannya;
2. file, layanan, tabel, dan data yang terdampak;
3. manfaat dan risiko;
4. dampak keamanan, performa, biaya, serta pengguna;
5. rencana test dan rollback.

Tunggu persetujuan eksplisit. Jangan menganggap diam, instruksi umum, atau permintaan analisis sebagai izin melakukan tindakan berisiko.

## 5. Keamanan — prioritas tertinggi

Terapkan secure-by-default, least privilege, defense in depth, dan deny-by-default.

### 5.1 Autentikasi dan otorisasi

- Metode autentikasi final belum ditentukan. Jangan memilih atau mengintegrasikan provider login tanpa persetujuan.
- Autentikasi membuktikan identitas; autentikasi saja tidak otomatis memberi hak akses ke seluruh data.
- Lakukan pemeriksaan otorisasi di server untuk setiap operasi baca, ubah, hapus, ekspor, dan unduh.
- Jangan mempercayai `role`, email, `userId`, `rtId`, `rwId`, atau parameter izin yang dikirim client tanpa verifikasi server.
- Terapkan prinsip least privilege dan tolak akses secara default.
- Jangan mengandalkan penyembunyian tombol/menu di frontend sebagai pengamanan.
- Pastikan batas akses RT/RW diterapkan pada setiap query dan operasi, bukan hanya filter tampilan.
- Cegah IDOR/BOLA: setiap permintaan berdasarkan ID harus memverifikasi hak pengguna terhadap objek dan lingkup wilayah tersebut.

### 5.2 Data pribadi warga

- Terapkan minimisasi data: kumpulkan, simpan, dan tampilkan hanya data yang benar-benar diperlukan.
- Data identitas, alamat rinci, nomor telepon, dokumen, informasi keluarga, dan data pribadi lain tidak boleh tampil di portal publik kecuali pemilik telah menetapkan secara sah bahwa data tersebut memang publik.
- Jangan menaruh data pribadi, token, atau informasi sensitif pada URL, query string, log, analytics, pesan error, atau metadata yang dapat diakses pihak tidak berwenang.
- Jangan membuat endpoint pencarian, ekspor, autocomplete, atau daftar warga yang dapat membocorkan data massal.
- Terapkan validasi input di server, batas panjang/format, parameterized queries, dan output encoding.
- Periksa otorisasi untuk file/lampiran setiap kali diakses; URL yang sulit ditebak bukan kontrol akses.
- Gunakan data sintetis untuk fixture, demo, dan test. Jangan menyalin data warga nyata ke lingkungan pengembangan tanpa izin dan perlindungan yang sesuai.

### 5.3 TiDB Cloud dan akses database

- Kredensial TiDB Cloud hanya boleh digunakan di lingkungan server, tidak pernah di browser, bundle frontend, repository, atau source map publik.
- Jangan membuat koneksi database langsung dari client/browser.
- Simpan connection string dan secret di environment variables Vercel atau secret manager yang sesuai. Jangan mencetak nilainya ke log.
- Gunakan akun database dengan hak minimum yang diperlukan; hindari kredensial admin/root untuk aplikasi.
- Gunakan parameterized queries/prepared statements. Jangan menyusun SQL dengan menggabungkan input pengguna.
- Terapkan transaksi untuk operasi multi-langkah yang harus konsisten.
- Evaluasi connection pooling, batas koneksi, cold start, dan pola koneksi serverless Vercel sebelum memilih driver atau implementasi.
- Jangan membuka akses jaringan database secara luas. Ikuti mekanisme konektivitas, TLS, dan kontrol akses resmi TiDB Cloud.
- Jangan menonaktifkan verifikasi sertifikat TLS untuk mengatasi masalah koneksi.
- Jangan menjalankan DDL, migrasi, seed, reset, atau perubahan data produksi tanpa persetujuan.
- Jangan mengasumsikan semua fitur/driver MySQL kompatibel sempurna; verifikasi kompatibilitas dengan versi dan layanan TiDB Cloud yang benar-benar digunakan.
- Jangan menyalin kredensial atau data hasil query ke issue, commit, dokumentasi, atau percakapan.

### 5.4 Vercel dan deployment

- Perlakukan environment Development, Preview, dan Production secara terpisah.
- Pastikan secret production tidak terekspos ke Preview atau client bundle.
- Gunakan environment variables dengan scope yang tepat dan nama yang jelas; jangan memasukkan secret ke variabel publik seperti `NEXT_PUBLIC_*` atau padanan framework.
- Jangan mengubah domain, DNS, build command, output directory, deployment protection, atau konfigurasi production tanpa persetujuan.
- Preview deployment bukan bukti bahwa production aman atau siap.
- Jangan melakukan deploy production tanpa persetujuan eksplisit.
- Periksa log build dan runtime agar tidak memuat token, connection string, data pribadi, atau detail internal sensitif.

### 5.5 Keamanan aplikasi web

- Validasi semua input di server, sekalipun frontend sudah melakukan validasi.
- Gunakan output encoding sesuai konteks untuk mencegah XSS.
- Hindari `eval`, HTML mentah dari input pengguna, dan penggunaan `innerHTML` tanpa sanitasi yang benar.
- Terapkan perlindungan CSRF sesuai mekanisme autentikasi dan framework yang dipilih.
- Atur cookie session dengan atribut keamanan yang sesuai (`HttpOnly`, `Secure`, `SameSite`) bila menggunakan cookie.
- Terapkan rate limiting dan perlindungan penyalahgunaan pada endpoint sensitif.
- Jangan mengembalikan stack trace, query SQL, token, atau konfigurasi internal ke pengguna.
- Amankan upload: tipe file, ukuran, nama, penyimpanan, pemindaian bila tersedia, dan kontrol akses.
- Terapkan security headers yang sesuai dengan framework dan kebutuhan aplikasi; jangan menambahkan CSP yang merusak aplikasi tanpa pengujian.

## 6. Kecepatan akses dari Indonesia

Optimalkan pengalaman pada perangkat mobile dan jaringan seluler dengan bandwidth/latensi yang bervariasi.

1. Utamakan halaman awal ringan, cepat tampil, responsif, dan tetap berguna pada perangkat Android kelas menengah/bawah.
2. Minimalkan JavaScript, CSS, font, gambar, dependency, dan request pihak ketiga.
3. Hindari library besar jika fungsi sederhana dapat dibuat tanpa library.
4. Gunakan gambar terkompresi, ukuran responsif, format modern bila didukung, serta lazy loading untuk konten di bawah layar.
5. Hindari pengiriman data berlebihan. Gunakan pagination, filter, pencarian server-side, dan batas hasil.
6. Optimalkan query TiDB: pilih kolom yang diperlukan, gunakan indeks sesuai pola query yang terukur, hindari N+1 query, dan periksa query plan untuk query penting.
7. Jangan menambah indeks secara membabi buta; indeks juga menambah biaya penyimpanan dan beban tulis.
8. Kurangi perjalanan bolak-balik browser-server-database. Gabungkan operasi yang aman dan relevan tanpa membuat response terlalu besar.
9. Cache hanya data yang aman untuk di-cache. Jangan mencampur cache publik dan data personal/antar pengguna. Tentukan TTL, invalidasi, dan variasi cache dengan benar.
10. Evaluasi lokasi region Vercel dan TiDB Cloud serta jarak jaringan di antara keduanya. Pilih konfigurasi yang mengurangi latensi untuk pengguna Indonesia dan koneksi aplikasi-database; jangan mengasumsikan region tertentu paling cepat tanpa pengukuran.
11. Hindari request ke layanan luar negeri yang tidak diperlukan. Pertimbangkan latensi nyata dari Indonesia, ketersediaan, biaya, dan ketergantungan vendor.
12. Sediakan loading, empty state, error state, dan retry yang jelas. Cegah aksi ganda saat request masih berjalan.
13. Jangan mengorbankan validasi, otorisasi, atau perlindungan data demi mengejar skor performa.
14. Jangan menjanjikan angka performa tanpa pengukuran yang dapat diulang.

### Metrik yang diperhatikan

- Core Web Vitals: LCP, INP, dan CLS.
- TTFB dan waktu respons API.
- Ukuran transfer awal, JavaScript yang harus diunduh, serta jumlah request.
- Latensi aplikasi ke TiDB Cloud.
- Durasi query database, jumlah query per request, dan tingkat error.
- Pengalaman nyata dari jaringan Indonesia, termasuk koneksi seluler.

Laporkan kondisi pengukuran (lokasi, perangkat, jaringan, cache, dan lingkungan) agar perbandingan adil. Gunakan target Core Web Vitals yang berlaku sebagai acuan, bukan klaim tanpa data.

## 7. Data, skema, dan migrasi

- Perlakukan data produksi sebagai data yang tidak boleh dikorbankan.
- Jangan melakukan reset, truncate, drop, atau penghapusan massal tanpa persetujuan eksplisit.
- Sebelum migrasi yang disetujui, siapkan backup yang dapat dipulihkan, rencana rollback, dan langkah verifikasi.
- Utamakan migrasi yang kompatibel dan bertahap jika memungkinkan.
- Dokumentasikan perubahan tabel, kolom, indeks, constraint, relasi, dan dampaknya terhadap aplikasi.
- Gunakan primary key dan relasi yang stabil; jangan mengandalkan urutan baris.
- Terapkan constraint dan validasi untuk menjaga integritas data.
- Pertimbangkan audit trail untuk perubahan data penting, dengan tetap menghindari penyimpanan data sensitif berlebihan.

## 8. UI/UX dan aksesibilitas

- Jangan mendesain ulang halaman yang ada kecuali diminta.
- Pertahankan istilah, bahasa, navigasi, dan pola visual proyek.
- Utamakan mobile-first, keterbacaan, form sederhana, dan penggunaan hemat bandwidth.
- Sediakan label form, pesan validasi yang jelas, fokus keyboard, kontras memadai, dan status loading/error.
- Jangan menampilkan informasi sensitif hanya karena lebih mudah untuk desain.
- Jangan mengorbankan kejelasan alur layanan warga demi animasi atau dekorasi.

## 9. Dependensi dan kualitas kode

- Jangan menambah dependency tanpa alasan yang jelas.
- Sebelum menambah dependency, periksa ukuran, lisensi, aktivitas pemeliharaan, kerentanan, kompatibilitas, dan alternatif yang lebih ringan.
- Hindari dependency yang tidak diperlukan dan dependency yang ditinggalkan.
- Jangan melakukan upgrade mayor atau mengganti package manager tanpa persetujuan.
- Ikuti pola kode dan konvensi repository yang sudah ada.
- Hindari abstraksi berlebihan, duplikasi yang tidak perlu, dan refactor di luar ruang lingkup.
- Jangan menambahkan Firebase atau GAS sebagai jalan pintas.

## 10. Pengujian dan verifikasi

Setelah perubahan, lakukan pemeriksaan yang relevan:

1. Jalankan formatter, linter, type check, unit test, integration test, atau build yang tersedia.
2. Uji alur sukses, input tidak valid, kegagalan database/API, dan kondisi jaringan yang relevan.
3. Uji akses tanpa login, role tidak sesuai, akses lintas RT/RW, dan akses objek milik pengguna lain sesuai ruang lingkup.
4. Periksa kebocoran data pribadi melalui response, URL, log, cache, dan halaman publik.
5. Untuk perubahan database, verifikasi query, transaksi, constraint, indeks, dan kompatibilitas migrasi pada lingkungan non-production terlebih dahulu.
6. Untuk perubahan UI, periksa tampilan mobile dan desktop.
7. Periksa diff agar tidak ada file atau perubahan tak terkait.
8. Jika test tidak dapat dijalankan, nyatakan dengan jelas apa yang belum diuji dan alasannya.
9. Jangan mengklaim aman, cepat, bebas bug, atau siap produksi hanya karena build berhasil.

## 11. Format laporan pekerjaan

Akhiri pekerjaan dengan ringkasan:

- **Permintaan:** tujuan yang dikerjakan.
- **Analisis:** kondisi awal dan temuan relevan.
- **Perubahan:** file dan bagian yang diubah.
- **Alasan:** kaitan perubahan dengan instruksi pengguna.
- **Keamanan:** kontrol yang ditambahkan/diperiksa dan risiko tersisa.
- **Performa Indonesia:** dampak yang diharapkan dan hasil ukur aktual bila tersedia.
- **Pengujian:** perintah/test yang dijalankan dan hasil sebenarnya.
- **Belum dilakukan:** hal yang belum diuji atau memerlukan akses/persetujuan.
- **Persetujuan lanjutan:** tindakan yang masih menunggu keputusan pengguna.

Jangan menyatakan deployment production berhasil jika hanya melakukan perubahan lokal atau Preview deployment.

## 12. Urutan prioritas jika ada konflik

1. Instruksi eksplisit terbaru dari pemilik proyek, sepanjang tidak melanggar hukum dan prinsip keamanan.
2. `AGENTS.md` ini.
3. `.github/copilot-instructions.md`.
4. Dokumentasi teknis dan keputusan proyek yang sudah disetujui.
5. Asumsi atau preferensi agen.

Jika instruksi baru bertentangan dengan keputusan teknologi atau kebijakan sebelumnya dan berdampak besar, jelaskan konflik lalu minta konfirmasi. Jangan memilih sendiri.

## 13. Prinsip akhir

**Tetap fokus pada instruksi pengguna. Jangan asal edit. Jangan menetapkan teknologi yang belum diputuskan. Jangan melakukan perubahan berisiko tanpa persetujuan. Lindungi data warga. Utamakan KEAMANAN dan KECEPATAN AKSES nyata dari Indonesia.**
