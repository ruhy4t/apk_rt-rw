# Import warga dari Excel `.xlsx`

## Lingkungan dan cakupan

Target aplikasi adalah satu RW dengan beberapa RT. Database development menggunakan MySQL Laragon; production menggunakan TiDB Cloud melalui server Vercel. Import tidak membuat RW baru, tidak membuat akun login, dan tidak mengganti username/password. Data warga harus masuk melalui model/enkripsi yang sama dengan formulir manual; jangan melakukan bulk insert plaintext yang melewati model.

Implementasi yang sedang disiapkan memakai **pembaca OOXML terbatas berbasis `ZipArchive` dan DOM**, khusus format template aplikasi. Ini bukan pengganti umum Excel/PhpSpreadsheet dan tidak mendukung seluruh fitur `.xlsx`. Repository belum memiliki parser spreadsheet eksternal; pendekatan ini tidak menambah dependency atau vendor. PHP lokal mempunyai `zip`, `dom`, `xmlreader`, `xmlwriter`, `mbstring`, dan `fileinfo`. Dockerfile menggunakan PHP 8.4 dan memasang ZIP; ekstensi yang diperlukan tetap harus diverifikasi pada image hasil build.

Alternatif bila kelak diperlukan dukungan Excel yang lebih luas: OpenSpout 4.x memiliki lisensi MIT dan deklarasi dukungan PHP 8.3/8.4/8.5. Library itu belum dipasang. Menambahnya perlu penilaian versi, lisensi, ukuran aktual artefak, advisori keamanan, serta persetujuan pemilik. Tidak ada biaya lisensi terpisah; penggunaan CPU/storage/database tetap mempunyai biaya operasional sesuai layanan. [Sumber proyek dan lisensi](https://github.com/openspout/openspout), [kebutuhan 4.x](https://raw.githubusercontent.com/openspout/openspout/4.x/composer.json).

## Penyimpanan berkas dan hasil impor

Workbook unggahan hanya dibaca dari berkas sementara upload PHP pada request pratinjau. Aplikasi tidak menyalin atau mengarsipkannya ke disk lokal/publik, B2, tabel database, atau lampiran. PHP membersihkan upload sementara pada akhir request. Konfirmasi memakai data yang telah dibaca dalam paket terenkripsi berumur maksimal 10 menit, bukan membaca ulang workbook tersimpan.

Database baru menerima data setelah konfirmasi dan validasi ulang. Semua data pribadi warga masuk melalui model terenkripsi: nama, hubungan keluarga, alamat, tempat/tanggal lahir, jenis kelamin, agama, status perkawinan, pendidikan, pekerjaan, status kependudukan, NIK, KK, dan telepon. ID relasi, kode RT/rumah tangga, serta digest pencarian tetap merupakan kolom operasional. Impor ditolak bila enkripsi tambahan belum aktif. Tidak ada perubahan skema baru untuk aturan penyimpanan ini.

Foto pengumuman, agenda, dan galeri menggunakan B2 privat melalui server aplikasi. Hanya metadata foto yang disimpan di database; isi foto tidak menjadi blob database dan tidak disimpan permanen pada disk Vercel. Kredensial B2 tetap hanya digunakan di server. Ini tidak berarti browser mengakses B2 menggunakan kredensial aplikasi.

## Alur yang harus terlihat

1. **Unduh template** dari Data warga. Petunjuk menjelaskan kolom wajib, daftar nilai yang diterima, serta format identitas sebagai teks.
2. **Pilih file dan wilayah RT**, dibatasi lingkup pengurus di server. Pengurus RT tidak boleh memilih RT lain; pengurus RW hanya pada RW aplikasi. Nilai dari client tidak dipercaya sebagai otorisasi.
3. **Periksa data** tanpa menyimpan warga: tampilkan jumlah keluarga/anggota valid dan kesalahan per baris. NIK, KK, dan telepon tetap disamarkan 2 digit awal + bintang + 1 digit akhir. Jangan mengirim seluruh data asli lewat hidden field JSON, URL, atau log.
4. **Konfirmasi import** hanya jika semua baris valid. Paket pratinjau dienkripsi, terikat akun/session, berumur maksimal 10 menit, dan memakai nonce sekali pakai. Simpan hanya nonce/status yang diperlukan pada session bersama; tidak menyimpan berkas asli atau plaintext warga di session/browser.
5. **Validasi ulang saat konfirmasi**, termasuk hak akses, RW aktif, duplikasi, serta perubahan database sejak pratinjau. Penguncian dan transaksi memastikan pengiriman ulang atau dua petugas tidak menghasilkan keluarga/kepala ganda.
6. **Hasil** menampilkan jumlah yang tersimpan serta kegagalan umum tanpa rincian pribadi. Bukan pembaruan otomatis data lama: import awal bersifat tambah baru; NIK/KK yang sudah ada ditolak untuk ditinjau, tidak ditimpa atau digabung tanpa keputusan.

## Template dan identitas

Satu baris merepresentasikan satu warga. Anggota dengan nomor KK sama dikelompokkan ke satu rumah tangga; harus ada tepat satu Kepala Keluarga pada setiap keluarga baru. Informasi RT, kode/alamat keluarga, serta data kepala keluarga antarbaris harus konsisten. NIK harus unik pada file maupun database. Nomor KK dapat berulang hanya untuk anggota keluarga yang sama.

NIK dan KK wajib **teks 16 digit**, termasuk nol di depan. Telepon juga teks. Jangan menerima sel numerik, notasi ilmiah, hasil formula, atau angka yang hanya diberi format tampilan 16 digit: Excel dapat membuang nol atau mengubah presisi, dan parser tidak dapat memulihkan angka aslinya dengan menebak. Tanggal lahir ditulis teks ISO `YYYY-MM-DD`; serial tanggal Excel dan locale ambigu ditolak. Gunakan template, header, serta nama sheet yang disediakan aplikasi. Field tidak dikenali, tambahan sheet tersembunyi, header ganda, dan kolom di luar template tidak boleh dilewati diam-diam.

OOXML mempunyai shared strings (`t="s"`) serta inline strings (`t="inlineStr"`); parser perlu memahami keduanya dalam subset template. Formula memiliki elemen `<f>` dan dapat menyertakan hasil cache di `<v>`: hasil cache tersebut tidak boleh dianggap sebagai input biasa. Hubungan workbook/sheet harus diperiksa, bukan menebak sheet hanya dari nama file ZIP. [Shared string table Microsoft](https://learn.microsoft.com/en-us/office/open-xml/spreadsheet/working-with-the-shared-string-table), [struktur sel dan formula](https://learn.microsoft.com/en-us/dotnet/api/documentformat.openxml.spreadsheet.cell), [nilai sel](https://learn.microsoft.com/en-us/dotnet/api/documentformat.openxml.spreadsheet.cellvalue), [relasi internal/eksternal OPC](https://learn.microsoft.com/en-us/dotnet/api/system.io.packaging.packagerelationship.targetmode).

## Batas awal dan perlindungan parser

Batas rancangan awal: **1 MiB file terkompresi dan 200 baris data**. Ini batas aplikasi yang konservatif, bukan angka performa hasil pengukuran. Batas akhir tetap harus sesuai konstanta implementasi dan hasil benchmark.

Sebelum membaca XML, periksa signature ZIP/struktur XLSX, nama/path entry, jumlah entry (maksimal 64), total ukuran tak terkompresi (maksimal 8 MiB), ukuran per entry (maksimal 2 MiB; shared strings dapat diberi maksimum khusus 4 MiB), dan rasio kompresi. Tolak path traversal, entry duplikat, ZIP terenkripsi/rekursif, dan arsip mencurigakan. Baca entry di dalam ZIP dengan pembatas byte, **tanpa mengekstrak** ke direktori aplikasi. Jangan bergantung hanya pada ukuran yang diklaim metadata ZIP.

Parse XML tanpa DTD/entity expansion, dengan akses jaringan dimatikan. Tolak formula apa pun, macro/VBA, OLE/embedded objects, external links/relationships, hyperlink eksternal, workbook berpassword, serta struktur/tipe sel yang tidak didukung. Jangan menghitung formula, memanggil URL relasi, atau mencoba memperbaiki file tidak valid. Batasi panjang/nilai setiap sel, jumlah sel, indeks shared-string, nomor baris/kolom, dan ukuran JSON hasil normalisasi sebelum membuat token terenkripsi. Batas file ZIP saja tidak menjamin ukuran payload konfirmasi kecil.

Normalisasi dan validasi tetap di server. Gunakan output encoding pada pratinjau; jangan memasukkan isi sel ke HTML mentah. Pesan kesalahan cukup nomor baris, nama kolom, dan alasan; jangan menyalin identitas asli. Audit memuat pelaku, lingkup, waktu, dan jumlah record, bukan isi file atau nomor identitas. Lindungi endpoint dengan autentikasi, CSRF, otorisasi ulang, rate limit, dan pencegahan aksi ganda.

## Vercel

Konfigurasi repository saat diperiksa: satu service `app`, runtime `container`, `Dockerfile.vercel`, region `sin1`, dan rewrite ke service yang sama. Jangan menggantinya hanya untuk import. Dokumentasi Container Runtime menyatakan limit Vercel Functions berlaku juga pada container; batas body request Functions adalah **4,5 MB**, termasuk overhead multipart/payload. Batas aplikasi 1 MiB berada di bawah itu. Timeout/memori efektif tetap perlu dicek pada project/plan yang digunakan, bukan diasumsikan dari Laragon. [Container Runtime](https://vercel.com/docs/functions/runtimes/container), [limit Functions](https://vercel.com/docs/functions/limitations).

Instance bisa diskalakan: jangan mengandalkan filesystem lokal sebagai penyimpanan bersama antara upload, preview, dan konfirmasi. Berkas sementara hanya untuk request yang sedang berjalan dan dibersihkan pada jalur sukses maupun gagal. Token pratinjau terenkripsi harus berukuran terbatas dan session/nonce berada pada penyimpanan bersama yang sudah disetujui. Jangan menyimpan file asli pada folder publik, repository, build output, atau log. Tidak ada layanan storage/queue baru yang otomatis disetujui. Jika volume melampaui batas awal, pecah berkas; desain antrean atau storage tambahan memerlukan keputusan tersendiri.

## Pemeriksaan sebelum dipakai production

Gunakan fixture sintetis untuk sukses, file salah format, header/sheet salah, leading zero, formula/cache, external rel, ZIP bomb, file besar, row limit, NIK ganda, keluarga tanpa/lebih dari satu kepala, data wilayah lain, token berubah/kedaluwarsa/dipakai ulang, konflik setelah preview, kegagalan transaksi, dan retry. Pastikan rollback tidak menyisakan separuh keluarga. Uji image container sebenarnya serta TiDB non-production, pantau waktu/memori/ukuran request tanpa mencatat data pribadi. Tidak ada janji angka kecepatan Indonesia sebelum pengukuran tersebut.

Dokumen ini adalah kontrak/rancangan untuk implementasi yang dimiliki agen utama. Belum merupakan bukti import production, perubahan konfigurasi Vercel, atau impor data warga nyata.
