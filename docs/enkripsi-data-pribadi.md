# Enkripsi data pribadi dan penyamaran nomor

## Cakupan

Penyamaran NIK, nomor KK, dan telepon dilakukan di server: dua digit pertama dan satu digit terakhir dipertahankan; digit tengah menjadi `*`. Nomor dengan paling banyak tiga digit disamarkan seluruhnya. Format telepon dinormalisasi menjadi digit untuk tampilan. Nilai lengkap tidak ditanam pada HTML, atribut input, atau JSON model. Kolom telepon pada edit dikosongkan: kosong berarti tetap, sedangkan penghapusan harus dipilih secara eksplisit.

NIK, nomor KK, dan agama tetap memakai enkripsi Laravel yang sudah ada. Enkripsi tambahan menyimpan payload `private_data` dengan cast Laravel `encrypted:array` pada:

- `residents`: nama, hubungan keluarga, telepon, tempat/tanggal lahir, jenis kelamin, status perkawinan, pendidikan, pekerjaan, dan status kependudukan.
- `households`: alamat.
- `users`: nama, nama pengguna, email. Password tetap di-hash.
- `service_requests`: judul, keterangan, tanggapan.
- `asset_loans`: keperluan peminjaman.
- `contributions` / `financial_entries`: catatan atau keterangan privat.
- `stored_files`: nama asli lampiran, bukan isi objek berkas.

Setelah konversi, kolom pribadi lama dikosongkan atau diganti penanda. Nama pengguna dan hubungan keluarga memakai digest HMAC untuk pencarian tanpa menyimpan nilai aslinya. ID, relasi, kode rumah tangga/wilayah, role/status layanan, tanggal/jumlah transaksi, dan informasi yang memang dipublikasikan tetap dapat di-query. Jangan memasukkan data pribadi ke kode wilayah/rumah tangga atau konten publik. Pengurutan akun dan pilihan warga menggunakan ID, karena nama terenkripsi tidak dapat diurutkan langsung di SQL.

Nama dan profil masih ditampilkan terbaca hanya melalui halaman yang sudah berwenang. Tidak ada endpoint pembukaan nomor lengkap atau perluasan role. Ciphertext rusak ditolak; sistem tidak mencoba menganggapnya plaintext. Seluruh payload dan atribut pribadi dikecualikan dari serialisasi model, serta input pribadi yang relevan tidak di-flash pada validasi gagal.

## Status dan urutan aktivasi

Enkripsi tambahan **belum aktif secara default** agar kode tidak mencoba menulis kolom yang belum dimigrasikan. Penyamaran nomor tetap bekerja tanpa aktivasi ini. Jangan menganggap data lama sudah terenkripsi sebelum konversi selesai.

1. Verifikasi lingkungan tujuan serta backup database dan kunci yang dapat dipulihkan. Simpan backup di luar repository. Uji pemulihan pada lingkungan terisolasi.
2. Uji migrasi dan konversi pada salinan non-production dengan versi TiDB yang sama. Pengujian SQLite bukan bukti kompatibilitas TiDB atau penguncian konkurensi production.
3. Batasi penulisan selama pergantian versi dan konversi. Semua instance penulis harus menggunakan versi baru; versi lama bisa mengembalikan plaintext atau menimpa data yang telah dikonversi.
4. Jalankan hanya migrasi penambahan kolom yang disetujui: `php artisan migrate --path=database/migrations/2026_11_15_000000_add_encrypted_personal_data.php --force`. Migrasi ini menambah kolom nullable pada delapan tabel, tanpa memindahkan data otomatis.
5. Aktifkan `PERSONAL_DATA_ENCRYPTION_ENABLED=true` pada lingkungan yang kolomnya sudah tersedia, lalu muat ulang konfigurasi/proses melalui mekanisme deployment yang disetujui. Jangan mengubah `APP_KEY`.
6. Jalankan `php artisan app:encrypt-personal-data` untuk menghitung record lama tanpa mengubahnya. Setelah backup benar-benar diverifikasi, jalankan `php artisan app:encrypt-personal-data --apply --backup-confirmed` dari server tepercaya dengan koneksi yang benar. Flag ini merupakan pernyataan operator bahwa backup telah diverifikasi; perintah tidak membuat atau memverifikasi backup sendiri.
7. Konversi memakai transaksi per 100 record dan penguncian baris; isi data tidak dicetak. Kegagalan mengembalikan chunk berjalan; chunk sebelumnya tetap tersimpan dan perintah bisa dilanjutkan ulang. Record yang sudah memiliki payload dilewati. Pembaruan pertama terhadap record lama juga mengenkripsi seluruh field pribadinya, termasuk field yang tidak sedang diedit.
8. Pastikan hitungan record belum dikonversi menjadi nol. Verifikasi login akun lama/baru, keunikan username, hak akses RT/RW, pergantian kepala keluarga, penyimpanan/edit, masking HTML, dan akses lampiran sebelum membuka penulisan kembali.

## Pemulihan dan batas perlindungan

Jangan menurunkan versi atau mematikan flag setelah konversi: versi lama tidak memahami payload, digest login, atau kolom yang sudah dikosongkan. Gunakan perbaikan maju atau pemulihan backup yang sudah diuji. `down()` migrasi sengaja menolak penghapusan payload agar tidak menghapus satu-satunya salinan data. Jangan merotasi `APP_KEY` tanpa rencana untuk enkripsi **dan** seluruh digest pencarian. Lindungi salinan kunci secara terpisah dari backup database.

Perubahan ini tidak mengenkripsi ulang isi objek lampiran di storage, backup lama, log historis, atau data yang telah diekspor. Hal-hal tersebut memerlukan pemeriksaan akses, kebijakan retensi, dan rencana konversi terpisah. Enkripsi tidak mencegah pembacaan oleh server yang memiliki kunci dan otorisasi, serta bukan jaminan aplikasi bebas kebocoran. Jangan mengklaim seluruh production aman sebelum konfigurasi, backup, log, storage, dan konversi nyata diverifikasi.
