# SQL skema idempoten — TiDB production dan MySQL lokal

Gunakan **satu berkas utama**: `D:\laragon\www\aplikasi-rt\docs\UPDATE-DATABASE.sql`. File yang sama menyiapkan pembaruan akun/identitas warga, kolom enkripsi, dan galeri untuk **MySQL 8+ Laragon maupun TiDB dengan foreign key aktif (6.6+)**. Pilih database tujuan yang benar lalu jalankan seluruh file melalui satu koneksi. Jalankan terpisah bila kedua lingkungan perlu diperbarui. Tidak perlu mengimpor lagi file per bagian `00`?`04` setelah memakai paket ini. File per bagian di folder ini dipertahankan sebagai referensi teknis. Paket lama `UPDATE-DATABASE-TIDB.sql` telah digantikan oleh nama tunggal ini.

Backup yang teruji dan uji pada salinan engine/versi yang sama tetap wajib sebelum perubahan. Hentikan pada error pertama; jangan menjalankan import bersamaan. Skrip melewati nama struktur yang sudah ada, tetapi tidak memperbaiki definisi yang berbeda, mengaktifkan enkripsi, atau mengenkripsi data lama. TiDB mendokumentasikan `PREPARE` dan `EXECUTE` sebagai kompatibel MySQL: [PREPARE TiDB](https://docs.pingcap.com/tidb/stable/sql-statement-prepare/), [EXECUTE TiDB](https://docs.pingcap.com/tidb/stable/sql-statement-execute/), [prepared statements MySQL](https://dev.mysql.com/doc/refman/8.0/en/sql-prepared-statements.html). Paket gabungan baru diperiksa statis, belum diuji eksekusi ulang pada engine MySQL/TiDB.


File ini disiapkan untuk **review dan impor manual**, belum dijalankan oleh agen pada database mana pun. MySQL Laragon adalah lokal; TiDB Cloud adalah production. Tidak ada koneksi, kredensial, nama database production, data warga, atau pengaturan Vercel di dalam SQL.

## Urutan penggunaan

1. Pilih database tujuan di klien SQL. Jalankan `00-preflight.sql` dan pastikan versi/engine, database terpilih, tabel dasar, serta jumlah RW benar. Bila terdapat lebih dari satu RW, tentukan penanganannya bersama pemilik; skrip tidak menghapus, menggabungkan, atau memilih RW sendiri.
2. Siapkan backup yang benar-benar pernah diuji pemulihannya. Uji berkas SQL pada salinan non-production dengan engine dan versi yang sama. Jangan menganggap keberhasilan di MySQL lokal sebagai bukti kompatibilitas TiDB production.
3. Pilih **satu** dialek. Untuk TiDB gunakan `01-identity-tidb.sql` lalu `02-private-data-tidb.sql`. Untuk MySQL 8 gunakan `01-identity-mysql8.sql` lalu `02-private-data-mysql8.sql`. Berkas `01` hanya diperlukan bila kolom/indeks pendahulu belum lengkap; menjalankannya ulang akan melewati nama yang sudah ada.
4. Import berkas utuh melalui koneksi yang sama, secara serial, dan hentikan pada error pertama. Jangan menjalankan migrasi skema bersamaan dari aplikasi/klien lain. DDL tidak menjadi satu transaksi besar yang dapat di-rollback seluruhnya.
5. Periksa hasil metadata di akhir setiap berkas dan jalankan `03-verify.sql`. Delapan kolom `private_data` harus `LONGTEXT NULL`; kedua indeks digest harus unik pada kolom yang benar. Perbaikan definisi yang tidak sesuai harus direview terpisah.
6. Baru setelah skema sesuai, lanjutkan prosedur aplikasi pada [panduan enkripsi](../enkripsi-data-pribadi.md). Jangan langsung menyalakan enkripsi atau melakukan deploy production hanya karena SQL selesai.

## Arti idempoten dan batasnya

Untuk penambahan galeri foto/video pada rilis berikutnya, gunakan `04-gallery-tidb.sql` atau `04-gallery-mysql8.sql` sesuai engine. Keduanya hanya membuat tabel `gallery_items` bila belum ada, beserta indeks publikasi dan foreign key. Foto pengumuman/agenda memakai `stored_files` yang sudah ada. Login CAPTCHA dan penguncian lima menit memakai tabel cache bersama yang sudah ada; tidak memerlukan kolom baru. Rincian penerapan ada di `docs/rilis-login-media.md` (dari root repository). SQL galeri tidak mengaktifkan penyimpanan B2, tidak mengubah env, dan tidak mengenkripsi record lama.

TiDB menyediakan `ADD COLUMN IF NOT EXISTS` dan `CREATE INDEX IF NOT EXISTS`. Skrip MySQL 8 menggunakan `information_schema`, `PREPARE`, dan `EXECUTE`, karena sintaks penambahan kolom MySQL 8 tidak menyediakan klausa yang sama. Tidak ada stored procedure yang dibuat. Pengulangan serial pada skema yang sudah sesuai tidak menambah kolom/indeks lagi. [Sintaks TiDB untuk kolom](https://docs.pingcap.com/tidb/stable/sql-statement-add-column/), [indeks TiDB](https://docs.pingcap.com/tidb/stable/sql-statement-create-index/), [ALTER MySQL 8](https://dev.mysql.com/doc/refman/8.0/en/alter-table.html), [prepared statements MySQL](https://dev.mysql.com/doc/refman/8.0/en/sql-prepared-statements.html).

Pemeriksaan keberadaan memakai **nama**, bukan pembuktian kesamaan seluruh definisi. Skrip tidak diam-diam mengubah tipe/isi kolom yang sudah ada, tidak memperbaiki indeks salah definisi, dan tidak menyelesaikan NIK/KK duplikat. Jika `private_data` sudah bertipe `TEXT` dari versi percobaan, jangan lanjut konversi; kapasitas perlu ditingkatkan ke `LONGTEXT NULL` melalui perubahan yang direview. Bila kolom atau tabel dasar hilang, error harus diselesaikan dahulu. Tidak ada klaim bahwa skrip tahan terhadap dua import DDL bersamaan.

`01` sesuai penambahan `users.must_change_password` serta identitas warga pada migrasi 2026-06-03/04. `02` sesuai migrasi `2026_11_15_000000_add_encrypted_personal_data.php`. Tabel aplikasi dasar harus sudah ada; ini bukan dump pemasangan baru.

## Hal yang sengaja tidak dilakukan SQL

- Tidak ada `DROP`, `TRUNCATE`, penghapusan, pemindahan, penggantian data warga, seed akun, atau perubahan secret.
- SQL hanya menyiapkan struktur. Enkripsi Laravel beserta HMAC bergantung pada format aplikasi dan `APP_KEY`; **SQL `AES_ENCRYPT` bukan pengganti yang kompatibel**. Konversi record lama dijalankan melalui perintah aplikasi yang sudah diuji, bukan dengan menaruh kunci di klien SQL.
- SQL tidak mengubah username, password, role, RT/RW, atau konfigurasi Vercel. Penambahan `must_change_password` yang belum ada memakai default seperti migrasi; kolom yang sudah ada dilewati.
- SQL tidak mencatat migrasi secara otomatis ke tabel `migrations`. Setelah impor manual, rekonsiliasikan riwayat bersama pengembang sebelum menjalankan `php artisan migrate` menyeluruh; migrasi identitas lama belum memakai guard keberadaan kolom. Jangan menjalankan dua metode penerapan untuk perubahan yang sama tanpa rekonsiliasi.

## Pemulihan dan verifikasi

Pertahankan kolom tambahan bila aplikasi perlu dikembalikan ke versi sebelumnya. Setelah record dikonversi, versi lama mungkin tidak memahami payload/digest: jangan menurunkan versi atau mematikan flag sembarangan. Gunakan rencana pemulihan backup atau perbaikan maju yang teruji. Simpan kunci secara terpisah dari backup database dan jangan merotasinya tanpa rencana enkripsi serta digest.

Agen baru melakukan pemeriksaan statis terhadap struktur berkas dan kesesuaiannya dengan migrasi. Belum ada eksekusi kedua kali pada MySQL disposable ataupun eksekusi TiDB, sehingga kompatibilitas engine aktual, hak akses DDL, durasi, dan dampak latensi masih perlu diuji. Tidak ada deployment production dilakukan oleh langkah ini.
