# Ketua RT/RW dan identitas penandatangan

Nama Ketua RT dan Ketua RW dicatat terpisah dari data warga dan akun petugas. Penetapan jabatan tidak otomatis membuat akun, memberikan peran, atau mengubah hak akses. Administrator mengelolanya melalui **Pengaturan wilayah**. Ketua dapat ditetapkan saat wilayah dibuat atau diganti kemudian. Nama yang disimpan dienkripsi dengan kunci aplikasi yang sudah ada; jangan mengganti kunci tanpa rencana pemulihan.

## Alur penggunaan

Halaman Pengaturan wilayah memisahkan **1. Identitas RW** dan **2. Daftar RT**. Isi kode/nama RW, Kelurahan/Desa, Kecamatan, Kota/Kabupaten, dan Ketua RW pada bagian pertama. Tiga identitas administrasi wajib lengkap saat membuat RW baru atau saat dikirim melalui formulir edit. RT mewarisi identitas administrasi RW induknya; isi nomor/nama RT dan Ketua RT melalui bagian kedua, lalu gunakan **Tambah RT baru** untuk RT berikutnya.

Bila data lama berisi beberapa RW karena RT keliru dimasukkan sebagai RW, tombol Tambah RT tetap terlihat tetapi nonaktif. Super Admin dapat membuka **Koreksi menjadi RT** pada baris yang memenuhi syarat, memilih RW tujuan, memeriksa kode/nama RT, dan menyetujui koreksi. Operasi ini membuat RT baru lalu menghapus catatan RW asal dalam satu transaksi. Proses ditolak bila sumber masih memiliki RT, akun RW, identitas administrasi, atau catatan penandatangan terkait. Riwayat penandatangan tidak dipindah atau ditulis ulang. Tidak ada koreksi otomatis oleh impor SQL.

Untuk kasus RW utama 05 dengan RT.02 dan 001 yang salah tercatat sebagai RW, koreksi kedua baris tersebut ke RW 05 satu per satu sebelum mengisi identitas RW utama. Setelah tersisa satu RW, tombol Tambah RT baru aktif. Jika ada penolakan keterkaitan, hentikan dan periksa datanya; jangan menghapus tabel/baris melalui SQL untuk melewati pemeriksaan.

1. Tambahkan satu RW dan wilayah RT yang diperlukan. Wilayah boleh disimpan tanpa ketua; statusnya **Belum ditetapkan**.
2. Isi nama Ketua RW untuk konten seluruh RW, dan nama Ketua RT untuk masing-masing RT. Nama tidak harus sudah ada pada data warga dan tidak memerlukan akun login.
3. Pengumuman dan agenda dapat disimpan sebagai draf sebelum ketua ditetapkan. Penerbitan konten tingkat RT memerlukan Ketua RT terkait. Konten seluruh RW memerlukan Ketua RW, bukan salah satu Ketua RT.
4. Permintaan surat layanan tetap dapat diajukan dan ditinjau. Penyelesaian surat menunggu penetapan Ketua RT sesuai wilayah rumah tangga pemohon. Penanganan aduan tidak memerlukan penandatangan surat.
5. Saat penerbitan atau penyelesaian surat, server menyimpan salinan nama dan jabatan penandatangan secara terenkripsi pada dokumen tersebut. Nilai dari form atau akun petugas bukan sumber identitas penandatangan.

Penandatanganan pada tahap ini berupa **nama dan jabatan untuk tanda tangan manual**. Tidak ada gambar tanda tangan, tanda tangan elektronik tersertifikasi, akun penandatangan baru, atau integrasi vendor. Pencatatan identitas tidak membuktikan dokumen sudah ditandatangani secara fisik.

## Pergantian ketua dan data lama

Super Admin dapat memperbaiki kode dan nama melalui **Pengaturan wilayah → Wilayah terdaftar → Ubah wilayah RT/RW**. Kode RT harus unik di dalam RW yang sama; kode RW harus unik. Form hanya menerima kode dan nama. ID wilayah, induk RW sebuah RT, relasi rumah tangga/akun, serta salinan penandatangan dokumen lama dipertahankan. Penetapan nama ketua tetap melalui formulir ketua yang terpisah. Perubahan wilayah dicatat pada audit tanpa menyalin nama ketua. Fitur koreksi ini memakai kolom yang sudah ada dan tidak menambah skema database.

Penggantian nama ketua berlaku untuk penerbitan baru. Salinan penandatangan pada dokumen yang sebelumnya telah diterbitkan tidak diganti otomatis, termasuk ketika judul atau keterangan diperbarui. Hal ini mencegah dokumen lama tampak diterbitkan oleh ketua pengganti.

Record lama yang sudah terbit sebelum fitur ini tidak boleh dianggap telah ditandatangani oleh ketua saat ini. Tidak ada pengisian massal atau penggantian data lama melalui SQL. Identitas yang belum tercatat perlu ditinjau administrator sesuai dokumen sumber.

Jika pengumuman atau agenda lama belum memiliki salinan penandatangan, pengeditan sambil tetap berstatus terbit ditolak. Simpan sebagai draf terlebih dahulu, tinjau ulang, lalu terbitkan kembali untuk mencatat penandatangan saat ini. Surat lama yang sudah selesai tetap tidak dapat diedit melalui alur pembaruan status; tidak ada penetapan ketua otomatis pada record tersebut.

## Penerapan database

Gunakan satu paket pembaruan struktur yang sama untuk MySQL 8+ Laragon atau TiDB yang mendukung foreign key:

```text
D:\laragon\www\aplikasi-rt\docs\UPDATE-DATABASE.sql
```

Pembaruan menambahkan `chairman_name` pada `rt_areas` dan `rw_areas`, serta `signatory_data` pada `service_requests`, `announcements`, dan `community_events`. Kolom baru berjenis `LONGTEXT NULL` untuk payload terenkripsi. Struktur lama yang namanya sudah ada dilewati; definisi berbeda tidak diperbaiki diam-diam. Ini menambah struktur tanpa mengisi nama ketua atau mengubah record warga.

Sebelum penerapan, siapkan backup yang teruji dipulihkan dan uji paket dua kali pada salinan non-production dengan engine/versi yang sesuai. Pilih database tujuan, jalankan melalui satu koneksi, dan hentikan pada error pertama. Menjalankan paket pada Laragon tidak memperbarui TiDB. Jangan mengimpor bagian SQL tambahan setelah memakai paket lengkap ini. SQL manual tidak merekonsiliasi tabel `migrations` secara otomatis.

Sebelum kolom baru tersedia, draf tetap dapat dibuat, tetapi penerbitan baru ditolak dengan informasi penyiapan. Jangan mematikan pemeriksaan tersebut untuk melewati pemasangan. SQL tidak mengaktifkan flag enkripsi data warga atau mengonversi data lama.

## Pengamanan dan batas verifikasi

Penetapan ketua dibatasi pada Administrator di server. Pemeriksaan lingkup RT/RW dan akses konten tetap mengikuti aturan aplikasi yang sudah ada. Nama ketua pada konten publik ditampilkan sebagai identitas jabatan yang memang dipakai menerbitkan konten tersebut, tanpa NIK, nomor KK, alamat, atau telepon. Halaman privat dan salinan penandatangan tidak boleh dimasukkan ke cache publik bersama.

Tes menggunakan data sintetis dan database terisolasi untuk draf, ketua belum ditetapkan, cakupan RT/RW, pergantian ketua, payload terenkripsi, input penandatangan palsu, serta skema belum tersedia. Tes tidak membuktikan validitas tanda tangan manual, penerapan pada TiDB produksi, tampilan browser perangkat nyata, atau latensi Indonesia. Tidak ada dependency, request pihak ketiga, atau layanan berbayar baru untuk pencatatan penandatangan.
