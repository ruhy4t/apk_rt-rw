# Penerapan fitur identitas warga

Kode fitur tidak menyertakan isi database, kredensial, `.env`, atau installer Laragon.

## Perubahan struktur

Migrasi `2026_06_04_000000_add_resident_identity_fields.php` menambah:

- `households`: nomor KK terenkripsi dan digest HMAC dengan indeks unik.
- `residents`: NIK terenkripsi, digest HMAC dengan indeks unik, tempat/tanggal lahir, jenis kelamin, agama terenkripsi, status perkawinan, pendidikan, pekerjaan, dan status kependudukan.

Kolom baru nullable agar baris lama tetap valid. Migrasi tidak mengisi identitas warga lama. Digest menggunakan kunci aplikasi; jangan mengganti `APP_KEY` tanpa rencana migrasi enkripsi dan digest.

## Urutan penerapan

1. Verifikasi backup TiDB dan kemampuan pemulihannya sebelum mengubah struktur production. Jangan menyalin backup ke repository.
2. Uji migrasi pada lingkungan non-production dengan versi TiDB yang sama, termasuk kolom nullable, indeks unik, dan penyimpanan nilai terenkripsi. Pengujian SQLite tidak membuktikan kompatibilitas TiDB.
3. Dengan konfigurasi server untuk database tujuan yang telah diverifikasi, periksa `php artisan migrate:status`, lalu terapkan hanya migrasi ini menggunakan `php artisan migrate --path=database/migrations/2026_06_04_000000_add_resident_identity_fields.php --force`. Jangan menjalankannya dengan konfigurasi database lokal untuk memperbarui production.
4. Pastikan migrasi selesai sebelum fitur dipakai. Migrasi tidak dijalankan otomatis saat build atau startup container.
5. Verifikasi deployment dari commit fitur pada domain production, lalu uji buka/tutup popup, validasi, simpan rumah tangga dan kepala keluarga, tambah/edit anggota, larangan akses lintas RT/RW, dan halaman publik. Gunakan data sintetis di non-production.

## Rollback

Kembalikan deployment aplikasi ke versi sebelum fitur dan pertahankan kolom tambahan beserta datanya. Jangan menjalankan `migrate:rollback` setelah data identitas disimpan: metode `down()` menghapus kolom dan data tersebut. Jika DDL gagal sebagian, periksa struktur aktual sebelum mencoba ulang; jangan menjalankan ulang secara membabi buta.

## Kontrol data

NIK, nomor KK, dan agama tidak di-flash sebagai input lama pada kegagalan validasi. Nomor identitas tidak diisi ulang pada HTML formulir edit. Endpoint pengelolaan memeriksa cakupan RT/RW di server, penulisan dibatasi lajunya, dan respons area autentikasi memakai `private, no-store`.
