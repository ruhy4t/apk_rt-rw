# Mockup visual APK-RT-RW

- [Buka prototipe lengkap](index.html): berkas HTML mandiri; dapat dibuka langsung tanpa server atau internet.
- [Lihat seluruh modul](overview.png): contact sheet visual dengan 15 panel. Ini sketsa desain, bukan tangkapan layar aplikasi produksi.
- [Pratinjau percakapan](rw-project-preview.html): fragmen untuk renderer visual percakapan yang menyediakan ikon Lucide.

Prototipe memuat 19 layar: portal publik, login, dashboard pengurus, dashboard warga, statistik, rumah tangga, detail keluarga, anggota keluarga, pengumuman, agenda, keuangan, inventaris, peminjaman, layanan, akun, pengguna, wilayah RT, impor XLSX, dan pratinjau impor. Ada 16 dialog contoh, navigasi per peran, serta pilihan tampilan desktop/HP.

Desain menggunakan satu RW dengan beberapa RT, ikon tindakan yang konsisten, penetapan/penggantian kepala keluarga, penyamaran nomor dengan dua digit awal dan satu digit akhir, username hanya baca, serta nama tampilan dan password yang dapat diubah. Alur impor menggambarkan batas 200 baris dan 1 MiB, pemeriksaan kesalahan, lalu konfirmasi tanpa menimpa otomatis.

Semua nama, angka, dan aktivitas adalah data sintetis. Tombol simpan dan impor hanya mendemonstrasikan interaksi; tidak menyimpan data, membaca isi XLSX, atau menghubungi API/database. Pemilih berkas hanya menampilkan nama berkas secara lokal. Jangan memasukkan password atau data warga sebenarnya ke prototipe.

Pemeriksaan: sintaks JavaScript, render 76 kombinasi layar/peran dan 16 dialog, tujuan navigasi, ID duplikat, larangan panggilan jaringan, serta render awal fragmen lulus pemeriksaan lokal. PNG sudah diperiksa secara visual. Tampilan dan interaksi HTML belum diverifikasi dalam browser karena akses browser pada sesi ini dibatasi. Artefak ini tidak mengubah aplikasi, database, konfigurasi Vercel, atau deployment.
