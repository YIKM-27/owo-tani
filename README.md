# PekaTani

PekaTani adalah aplikasi berbasis web pertanian untuk mengelola lahan, menjelajahi Niaga Tani, mencatat pesanan, dan melihat contoh harga komoditas pangan saat ini.

> Panduan instalasi lengkap, struktur aplikasi, dan detail teknis ada di [DOKUMENTASI.md](./DOKUMENTASI.md).

## Mulai menggunakan aplikasi

### Menjalankan pertama kali

1. Jalankan Apache dan MySQL dari Laragon.
2. Buat database MySQL `owo_tani`, lalu impor [`database/schema.sql`](./database/schema.sql) lewat HeidiSQL atau phpMyAdmin. Jangan ketik SQL langsung di Command Prompt.
3. Untuk database lama, jalankan migrasi yang belum diterapkan di folder `database/migrations/`. Instalasi baru dari schema terbaru tidak memerlukan migrasi.
4. Dari PowerShell pada folder proyek, atur nama database lokal lalu jalankan:

   ```text
   $env:DB_NAME='owo_tani'
   php database/seed.php
   php -S 127.0.0.1:8000 -t .
   ```

5. Buka `http://127.0.0.1:8000` di browser. Biarkan terminal tetap berjalan selama menggunakan aplikasi.

Gunakan Apache Laragon atau PHP server di atas, bukan tombol **Go Live / Live Server**. Live Server tidak menjalankan PHP atau menghubungkan database.

### Akun demo

| Peran | Email | Kata sandi |
|---|---|---|
| Admin | `admin@owotani.test` | `admin123` |
| Petani | `petani1@owotani.test` | `Petani01` |
| Penjual | `penjual1@owotani.test` | `Penjual01` |
| Petani dan Penjual | `taniniaga1@owotani.test` | `TaniJual01` |
| Warga / Wargi | `warga1@owotani.test` | `Warga001` |

Akun kedua untuk setiap peran juga tersedia; daftar lengkapnya ada di [DOKUMENTASI.md](./DOKUMENTASI.md). Akun demo hanya untuk penggunaan lokal dan kata sandinya harus diganti sebelum aplikasi dipublikasikan.

Untuk demo sementara, isi database cloud dengan akun non-admin dan 16 produk contoh: pilih database `main` di SQL Editor lalu jalankan [`database/demo_data.sql`](./database/demo_data.sql). Akun demo memakai kata sandi di tabel di atas dan dapat digunakan siapa pun yang mengakses situs; jangan impor ke situs produksi. Impor ini tidak membuat akun admin.

## Cara memakai

1. **Buat akun atau masuk.** Saat mendaftar, pilih peran yang sesuai. Peran dapat diubah kemudian melalui **Profil**.
2. **Gunakan menu di navigasi.**
   - **Beranda:** pintasan ke fitur utama dan pilihan produk.
   - **Niaga Tani:** cari produk, pesan produk yang tersedia, atau—jika memiliki akses jual—tambahkan, edit, dan hapus produk.
   - **Lahanku:** catat lahan, isi koordinat di tengah petak, lalu simpan catatan penyiraman, pemupukan, penanganan cuaca ekstrem, atau pengomposan.
   - **Harga Pasar:** pilih komoditas pada daftar untuk melihat grafik dan ringkasannya.
   - **Pesanan:** pembeli memantau belanja; penjual mengubah status pesanan yang masuk.
   - **Profil:** ubah nama, peran, dan foto profil.
3. **Untuk melihat cuaca lahan,** buka **Lahanku → Lihat prakiraan cuaca**. Alamat dan kode ADM4 dicari otomatis dari koordinat; kode tidak perlu dicari atau diketik sendiri.
4. **Untuk memasang foto,** pilih JPG, PNG, atau WebP hingga 5 MB pada formulir produk atau profil. Gambar disimpan di database agar tetap tersedia setelah deployment baru.

## Akses berdasarkan peran

| Peran | Lahanku | Melihat / memesan produk | Mengelola produk | Harga Pasar | Pesanan |
|---|---:|---:|---:|---:|---:|
| Petani | Ya | Ya | Tidak | Ya | Melihat belanja |
| Penjual | Tidak | Ya | Ya | Ya | Melihat pesanan terkait |
| Petani dan Penjual | Ya | Ya | Ya | Ya | Melihat pesanan terkait |
| Warga / Wargi | Tidak | Ya | Tidak | Ya | Melihat belanja |
| Admin | Akses pengelolaan | Akses pengelolaan | Akses pengelolaan | Ya | Melihat semua |

## Catatan data

- Harga dan grafik di **Harga Pasar adalah ilustrasi/demo**, bukan data harga aktual atau real-time. Gunakan Panel Harga Pangan Bapanas untuk memeriksa informasi resmi.
- Prakiraan cuaca menggunakan BMKG setelah koordinat dicocokkan dengan wilayah administrasi desa/kelurahan. Jika pencocokan atau layanan BMKG gagal, aplikasi mencoba Open-Meteo sebagai cadangan.
- Saran cuaca bersifat umum dan bukan pengganti penyuluh pertanian.
- Foto produk contoh dari internet memerlukan koneksi; foto yang diunggah pengguna disimpan di tabel `app_media`.

## Kontribusi terhadap SDGs

| Tujuan SDG | Fitur PekaTani | File terkait |
|---|---|---|
| SDG 2 — Tanpa Kelaparan | Hasil panen sortiran yang layak konsumsi dipasarkan dengan harga lebih terjangkau untuk mengurangi pangan terbuang. | [`marketplace.php`](./marketplace.php), [`database/seed.php`](./database/seed.php) |
| SDG 9 — Industri, Inovasi, dan Infrastruktur | Informasi asal produk, catatan logistik pesanan, serta halaman pemantauan harga mendukung alur niaga digital; data harga masih ilustrasi. | [`marketplace.php`](./marketplace.php), [`orders.php`](./orders.php), [`prices.php`](./prices.php) |
| SDG 13 — Penanggulangan Perubahan Iklim | Prakiraan cuaca, saran adaptasi umum, dan jurnal penanganan cuaca ekstrem membantu perencanaan lahan; saran bukan pengganti penyuluh. | [`weather.php`](./weather.php), [`land.php`](./land.php) |

## Bantuan cepat

- **Database tidak ditemukan:** untuk Laragon, pastikan `owo_tani` sudah dibuat, `DB_NAME` diatur ke `owo_tani`, dan [`database/schema.sql`](./database/schema.sql) berhasil diimpor. Hosting memakai `main` secara bawaan; atur `DB_NAME` jika nama databasenya berbeda.
- **Kolom database tidak ditemukan:** jika memakai database lama, jalankan migrasi satu kali seperti dijelaskan di atas.
- **Foto tidak bisa diunggah:** aktifkan ekstensi GD PHP dan pastikan tabel `app_media` sudah dibuat dari schema atau migrasi terbaru.
- **Halaman cuaca tidak menampilkan BMKG:** periksa koneksi internet dan cURL PHP. Aplikasi dapat beralih ke Open-Meteo jika tersedia.

cd "C:\Users\vestia\Documents\Belajar_HTML\PEMOGRAMAN WEEEB I"
php database\seed.php
php -S 127.0.0.1:8000 -t .
