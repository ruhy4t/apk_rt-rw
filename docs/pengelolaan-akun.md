# Pengelolaan akun

- Super admin mengelola akun petugas RW, petugas RT, dan warga di semua wilayah.
- Petugas RW mengelola petugas RT dan warga dalam RW-nya.
- Petugas RT mengelola warga dalam RT-nya. Warga tidak mengelola akun lain.
- Nama pengguna tetap. Setiap pengguna dapat mengubah nama dan kata sandinya sendiri.
- Buka **Menu > Profil dan kata sandi** untuk profil, atau **Menu > Pengguna** untuk pengelolaan akun sesuai hak akses.
- Kata sandi awal dan hasil reset harus diganti saat login berikutnya. Sampaikan kata sandi sementara melalui kanal yang aman.
- Reset atau penonaktifan mencabut sesi database akun yang bersangkutan. Perubahan kata sandi sendiri mencabut sesi lain. Fitur ini memerlukan driver sesi `database` pada koneksi database aplikasi.

Sebelum menerapkan pembaruan, siapkan backup yang dapat dipulihkan dan terapkan penambahan kolom `users.must_change_password` pada database yang benar. Pengujian dan deployment production memerlukan persetujuan terpisah. Untuk rollback, kembalikan deployment sebelumnya tanpa menghapus kolom atau data. Deployment sebelumnya tidak memaksa penggantian kata sandi sementara; sebelum rollback, nonaktifkan akun yang masih memiliki `must_change_password = 1`, lalu pulihkan aksesnya dengan prosedur aman setelah perbaikan.
