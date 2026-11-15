# Cakupan satu RW

Aplikasi ditujukan untuk satu RW dengan beberapa RT. Database production tetap TiDB Cloud; database lokal tetap MySQL Laragon. Tidak ada penggantian provider, penghapusan data wilayah, atau perubahan konfigurasi Vercel untuk penyesuaian ini.

## Perilaku

- Bila belum ada RW, administrator dapat menetapkan satu RW dari **Pengaturan wilayah**.
- Setelah satu RW tersimpan, tombol dan formulir **Tambah wilayah RW** tidak ditampilkan. Server menolak permintaan RW kedua, termasuk bila dikirim langsung tanpa melalui UI.
- Administrator dapat menambahkan beberapa RT di bawah RW yang sama. Formulir RT menampilkan RW sebagai nilai tetap dan mengirim ID yang divalidasi server.
- Bila data lama memiliki lebih dari satu RW, semua record tetap dipertahankan. Penambahan RW/RT ditolak dan halaman meminta keputusan wilayah. Tidak ada pemilihan RW pertama secara otomatis, pemindahan RT, penggabungan, atau penghapusan wilayah.
- Pemeriksaan akses RT/RW yang sudah ada tetap berlaku. Penyesuaian ini tidak otomatis mengubah cakupan akun atau menyembunyikan data lama dari administrator. Jika data production berisi beberapa RW, pemilik perlu menetapkan RW yang digunakan dan menyetujui rencana penataan data sebelum aplikasi dianggap sudah bercakupan tunggal sepenuhnya.

## Konsistensi

Pembuatan RW melalui aplikasi memakai satu transaksi. Baris administrator dengan ID terkecil dikunci sebagai titik serialisasi yang stabil, termasuk ketika tabel RW masih kosong. Pengecekan RW dilakukan dengan `SELECT ... FOR UPDATE` setelah lock administrator diperoleh, sehingga memakai pembacaan terkini, lalu pembuatan RW dan audit disimpan dalam transaksi yang sama. Transaksi dapat dicoba ulang maksimal tiga kali oleh Laravel saat terjadi konflik yang didukung driver.

Tidak ada skema baru untuk perubahan minimal ini. Batas satu RW diterapkan pada jalur tulis aplikasi, bukan constraint fisik yang mencegah insert SQL manual atau penulis eksternal. Jangan menjalankan penulisan wilayah lewat SQL manual atau aplikasi versi lama. Bila constraint tingkat database diinginkan, persiapkan perubahan terpisah beserta SQL idempoten dan pemeriksaan data lama terlebih dahulu.

## Verifikasi dan rollback

Test sintetis mencakup penetapan RW pertama, penolakan RW kedua, penambahan beberapa RT, penolakan perubahan pada data lama yang memiliki beberapa RW, akses role yang tidak berwenang, dan kompatibilitas akun terenkripsi. SQLite in-memory tidak membuktikan perilaku lock/konkurensi MySQL atau TiDB; pengujian dua request paralel perlu dilakukan pada lingkungan non-production yang sesuai sebelum klaim tersebut dibuat.

Rollback kode tidak menghapus RW/RT yang sudah disimpan. Namun, versi lama kembali memungkinkan penambahan banyak RW; hentikan penulisan pengaturan wilayah selama rollback sampai aturan tunggal diterapkan kembali. Tidak ada migrasi database atau SQL destruktif yang diperlukan oleh perubahan ini.
