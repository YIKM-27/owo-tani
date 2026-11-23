# Dokumentasi OwO Tani

Dokumen ini menjelaskan persiapan, susunan proyek, fitur, dan alur teknis aplikasi OwO Tani. Untuk panduan penggunaan sehari-hari, lihat [README.md](./README.md).

## 1. Gambaran proyek

OwO Tani dibuat dengan PHP native, MySQL/MariaDB, HTML, CSS, dan JavaScript tanpa framework. Aplikasi berjalan di Laragon atau container PHP dan menggunakan session database untuk mendukung deployment serverless.

### Persyaratan

- Laragon dengan PHP 8.1 atau lebih baru dan MySQL/MariaDB.
- Ekstensi PHP: PDO MySQL, cURL, mbstring, dan GD.
- Koneksi internet untuk layanan cuaca, geocoding, font, dan sebagian foto contoh.
- Database harus mengizinkan tabel sesi dan penyimpanan media aplikasi.

## 2. Instalasi dan konfigurasi

### Database baru

1. Jalankan Apache dan MySQL di Laragon.
2. Pilih database yang akan dipakai (hosting kamu memakai `main`) lalu impor [`database/schema.sql`](./database/schema.sql) ke database tersebut. File schema membuat tabel di database yang sedang dipilih.
3. Pengaturan koneksi bawaan di [`config/database.php`](./config/database.php):
   - Host: `127.0.0.1`
   - Database bawaan: `main`
   - Pengguna: `root`
   - Kata sandi: kosong
4. Aplikasi memakai database `main` secara bawaan. Atur environment variable `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, dan `DB_PASS` sesuai kredensial hosting.
5. Di terminal pada folder proyek, jalankan:

   ```text
   php database/seed.php
   ```

6. Jalankan server pengembangan:

   ```text
   php -S 127.0.0.1:8000 -t .
   ```

7. Buka `http://127.0.0.1:8000`. Alternatifnya, tempatkan proyek di `C:\laragon\www\owo-tani` dan gunakan Apache Laragon.

Live Server/Go Live hanya menyajikan berkas statis dan tidak cocok untuk menjalankan aplikasi PHP.

### Memperbarui database lama

Database lama perlu diperbarui dengan migrasi yang belum diterapkan. Buat cadangan terlebih dahulu, lalu jalankan migrasi sesuai urutan:

1. [`database/migrations/20261116_media_and_bmkg_locations.sql`](./database/migrations/20261116_media_and_bmkg_locations.sql) untuk database sebelum fitur foto dan integrasi ADM4.
2. [`database/migrations/20261122_sdg_features.sql`](./database/migrations/20261122_sdg_features.sql) untuk aktivitas jurnal baru, kategori hasil panen sortiran, asal produk, dan catatan pengiriman.
3. [`database/migrations/20261123_vercel_storage.sql`](./database/migrations/20261123_vercel_storage.sql) untuk session dan gambar yang disimpan lintas instance.

Lewati migrasi yang sudah pernah diterapkan. Jangan jalankan migrasi pada database baru dari `schema.sql` terbaru karena perubahan tersebut sudah tercakup.

## 3. Akun demo

Seeder membuat akun demo berikut. Kata sandi di-hash dengan `password_hash()`.

| Peran | Email | Kata sandi |
|---|---|---|
| Admin | `admin@owotani.test` | `admin123` |
| Petani 1 | `petani1@owotani.test` | `Petani01` |
| Petani 2 | `petani2@owotani.test` | `Petani02` |
| Penjual 1 | `penjual1@owotani.test` | `Penjual01` |
| Penjual 2 | `penjual2@owotani.test` | `Penjual02` |
| Petani dan Penjual 1 | `taniniaga1@owotani.test` | `TaniJual01` |
| Petani dan Penjual 2 | `taniniaga2@owotani.test` | `TaniJual02` |
| Warga 1 | `warga1@owotani.test` | `Warga001` |
| Warga 2 | `warga2@owotani.test` | `Warga002` |

Seeder idempoten untuk akun dan contoh produk yang sudah ada: menjalankannya lagi tidak membuat duplikat dengan email/nama produk dan penjual yang sama. Akun admin tidak dapat dibuat dari formulir pendaftaran. Ganti kata sandi demo sebelum aplikasi dipublikasikan.

## 4. Fitur dan akses

| Fitur | Petani | Penjual | Petani dan Penjual | Warga / Wargi | Admin |
|---|---:|---:|---:|---:|---:|
| Kelola lahan dan catatan perawatan | Ya | Tidak | Ya | Tidak | Akses pengelolaan |
| Lihat/pesan produk | Ya | Ya | Ya | Ya | Akses pengelolaan |
| Buat, edit, dan hapus produk sendiri | Tidak | Ya | Ya | Tidak | Akses pengelolaan |
| Lihat harga pasar | Ya | Ya | Ya | Ya | Ya |
| Pesanan | Pesanan pembelian | Pembelian dan penjualan | Pembelian dan penjualan | Pembelian | Semua pesanan |
| Ubah nama, foto, dan peran profil | Ya | Ya | Ya | Ya | Nama/foto |

Peran pengguna dapat diubah melalui halaman Profil. Daftar akses ditegakkan di server menggunakan `can_access()` dan `require_capability()` pada [`includes/bootstrap.php`](./includes/bootstrap.php).

### Autentikasi dan session

- Pendaftaran memvalidasi nama, email, peran, dan kata sandi minimum 8 karakter.
- Kata sandi disimpan sebagai hash `password_hash()` dan diperiksa dengan `password_verify()`.
- Session ID diperbarui saat login; cookie session menggunakan `HttpOnly` dan `SameSite=Lax`, dan `Secure` ketika HTTPS aktif.
- Formulir yang mengubah data memakai token CSRF.
- Fitur “ingat email” hanya menyimpan alamat email, bukan kata sandi.

### Niaga Tani dan pesanan

- Penjual dapat membuat, mengubah, dan menghapus produk miliknya.
- Pembeli membuat pesanan pada stok yang tersedia; stok dikurangi dalam transaksi database.
- Pesanan menyimpan salinan nama, jumlah, harga, dan total saat transaksi.
- Penjual mengubah status pesanan yang masuk melalui halaman Pesanan.

### Foto profil dan produk

- Formulir menerima JPEG/JPG, PNG, dan WebP, maksimal 5 MB per file.
- PHP memvalidasi isi sebagai gambar, membatasi resolusi, mengubah ukuran gambar besar, dan menyimpan ulang sebagai JPEG.
- Gambar disimpan sebagai BLOB pada `app_media`; kolom profil/produk menyimpan URL `media.php` yang memakai ID acak.
- Penyimpanan database dipakai agar foto tetap tersedia pada container Vercel yang filesystem-nya hanya sementara dan read-only.
- Atur `upload_max_filesize` dan `post_max_size` PHP jika deployment memerlukan ukuran unggahan berbeda.
- Foto Unsplash merupakan gambar fallback untuk produk yang belum memiliki foto unggahan.

### Lahanku dan cuaca BMKG

1. Pengguna memasukkan latitude dan longitude di tengah petak lahan.
2. Backend meminta reverse geocoding ke OpenStreetMap/Nominatim untuk mendapatkan alamat administratif.
3. Nama provinsi, kabupaten/kota, kecamatan, dan desa/kelurahan dicocokkan dengan API kode wilayah `wilayah.id`.
4. Kode desa/kelurahan ADM4 dikirim ke endpoint prakiraan publik BMKG:

   ```text
   https://api.bmkg.go.id/publik/prakiraan-cuaca?adm4=<kode-adm4>
   ```

5. Alamat dan kode ADM4 yang berhasil didapat disimpan pada record lahan, lalu prakiraan BMKG ditampilkan.
6. Jika reverse geocoding, pencocokan kode wilayah, atau BMKG gagal, prakiraan dicoba kembali melalui Open-Meteo dengan koordinat.

BMKG memberikan prakiraan wilayah administratif, bukan sensor persis di lahan. Pencocokan otomatis juga bisa gagal bila nama dari reverse geocoder berbeda dari data kode wilayah. Ketika itu terjadi, halaman menunjukkan alasan dan menggunakan sumber cadangan jika tersedia.

### Harga Pasar

Grafik, harga, dan perubahan harga saat ini merupakan data ilustrasi dalam [`prices.php`](./prices.php). Grafik tidak terhubung ke feed harga aktual, tidak diperbarui real-time, dan tidak boleh dipakai sebagai dasar transaksi. Halaman menyediakan tautan ke Panel Harga Pangan Bapanas untuk pengecekan informasi resmi.

## 5. Kontribusi terhadap SDGs

Fitur berikut mendukung tujuan pembangunan berkelanjutan dalam lingkup aplikasi. Ini bukan klaim bahwa aplikasi sendiri mencapai target SDG; data dan saran memiliki keterbatasan yang dijelaskan di bawah.

| Tujuan SDG | Fitur OwO Tani | File terkait |
|---|---|---|
| SDG 2 — Tanpa Kelaparan | Kategori hasil panen sortiran menghubungkan hasil yang masih layak konsumsi dengan pembeli dengan harga lebih terjangkau, untuk mengurangi pangan terbuang. | [`marketplace.php`](./marketplace.php), [`database/seed.php`](./database/seed.php), [`database/schema.sql`](./database/schema.sql) |
| SDG 9 — Industri, Inovasi, dan Infrastruktur | Label asal produk, catatan informasi pengiriman pesanan, dan pemantauan komoditas membantu menata alur niaga digital. Harga pada halaman pasar masih ilustrasi dan belum memakai API Bapanas. | [`marketplace.php`](./marketplace.php), [`orders.php`](./orders.php), [`prices.php`](./prices.php) |
| SDG 13 — Penanggulangan Perubahan Iklim | Prakiraan dan saran adaptasi cuaca membantu perencanaan aktivitas lahan; jurnal dapat mencatat penanganan cuaca ekstrem dan pengomposan. Saran cuaca bersifat umum, bergantung pada prakiraan wilayah, dan bukan pengganti penyuluh pertanian. | [`weather.php`](./weather.php), [`land.php`](./land.php), [`includes/location.php`](./includes/location.php) |

## 6. Skema data

Skema lengkap terdapat di [`database/schema.sql`](./database/schema.sql).

| Tabel | Kegunaan dan kolom utama |
|---|---|
| `users` | Nama, email unik, hash kata sandi, peran, path foto profil, waktu pembuatan. |
| `farms` | Pemilik, nama, tipe, luas, latitude/longitude, alamat, kode ADM4, tanaman utama. |
| `farm_logs` | Aktivitas penyiraman, pemupukan, penanganan cuaca ekstrem, atau pengomposan dan catatan per lahan. |
| `products` | Penjual, nama, kategori termasuk hasil panen sortiran, deskripsi, path foto, asal daerah opsional, harga, satuan, stok. |
| `orders` | Produk, pembeli, penjual, snapshot nama/harga, jumlah, total, status, dan catatan pengiriman opsional. |

Foreign key digunakan untuk menjaga relasi pengguna, lahan, produk, dan pesanan. Penghapusan lahan juga menghapus catatan perawatannya; penghapusan akun menghapus data terkait sesuai aturan foreign key.

## 7. Peta berkas

```text
.
├── assets/
│   ├── css/app.css           # Gaya aplikasi responsif
│   ├── js/app.js             # Navigasi, formulir, dan grafik harga
│   └── favicon.svg
├── config/database.php      # Koneksi PDO dari konfigurasi DB_*
├── database/
│   ├── schema.sql            # Skema untuk instalasi baru
│   ├── seed.php              # Akun dan produk demo
│   ├── demo_products.sql     # Produk demo untuk akun seller terpilih
│   └── migrations/           # Migrasi bertanggal
│       ├── 20261116_media_and_bmkg_locations.sql
│       ├── 20261122_sdg_features.sql
│       └── 20261123_vercel_storage.sql
├── includes/
│   ├── bootstrap.php         # Session, helper, akses, CSRF, unggah gambar
│   ├── header.php            # Header dan navigasi
│   ├── footer.php            # Footer
│   └── location.php          # Geocoding, pencocokan ADM4, klien BMKG
├── uploads/
│   ├── .htaccess
│   ├── products/             # Lokasi lama untuk foto produk
│   └── profiles/             # Lokasi lama untuk foto profil
├── dashboard.php
├── land.php
├── marketplace.php
├── orders.php
├── prices.php
├── profile.php
├── weather.php
├── index.php                 # Beranda dan ringkasan SDGs
├── login.php
├── register.php
└── logout.php
```

Foto baru tidak memerlukan folder `uploads/`; foto lama dengan path `uploads/` tetap didukung.

## 8. Sumber eksternal dan privasi

- **BMKG:** prakiraan cuaca publik untuk kode ADM4. Periksa atribusi dan batas penggunaan layanan saat deployment.
- **OpenStreetMap/Nominatim:** alamat dari reverse geocoding. Permintaan lokasi mengirim koordinat lahan; patuhi kebijakan penggunaan layanan Nominatim dan atribusi OpenStreetMap.
- **wilayah.id:** pencocokan nama wilayah administratif terhadap kode wilayah.
- **Open-Meteo:** sumber cadangan cuaca berbasis koordinat.
- **Unsplash dan Google Fonts:** foto fallback dan font antarmuka.

Koordinat lahan dikirim ke layanan geocoding/cuaca tersebut saat pengguna membuka prakiraan. Beri tahu pengguna dan tinjau kebijakan privasi sebelum penggunaan publik.

## 9. Pemecahan masalah

| Gejala | Pemeriksaan |
|---|---|
| Database tidak ditemukan | Pastikan database hosting (`main`) tersedia dan `DB_NAME` berisi nama database yang benar. |
| Kolom `avatar_path`, `image_path`, atau `bmkg_adm4` tidak ditemukan | Jalankan migrasi sekali pada database lama; jangan pada database baru dari schema terbaru. |
| PDO gagal tersambung | Pastikan nilai `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS` sesuai dan TLS diaktifkan bila database mewajibkannya. |
| PHP tidak dieksekusi | Jalankan melalui Apache Laragon atau `php -S 127.0.0.1:8000 -t .`, bukan Go Live. |
| Upload gagal | Aktifkan GD, pastikan tabel `app_media` tersedia, dan periksa batas PHP serta format/ukuran file. |
| Cuaca BMKG tidak tersedia | Pastikan cURL dan internet aktif; periksa alamat/kode ADM4. Open-Meteo digunakan jika tersedia. |
| Foto fallback tidak terlihat | Periksa koneksi internet ke Unsplash atau unggah foto sendiri. |

## 10. Catatan deployment

- Atur `DB_HOST`, `DB_PORT`, `DB_NAME=main`, `DB_USER`, dan `DB_PASS` pada environment hosting. TLS otomatis aktif untuk host database non-lokal; `DB_SSL` dapat mengubahnya secara eksplisit, dan `DB_SSL_CA` menerima path CA khusus bila diperlukan.
- Untuk database yang sudah berisi tabel, pilih database `main` lalu impor migrasi [`20261123_vercel_storage.sql`](./database/migrations/20261123_vercel_storage.sql). Sesi login dan gambar unggahan disimpan di database, bukan filesystem instance yang sementara.
- Impor [`database/schema.sql`](./database/schema.sql) pada instalasi baru dengan database `main` terpilih. Tabel `app_sessions` dan `app_media` diperlukan untuk sesi login dan gambar unggahan.
- Untuk menampilkan produk demo tanpa membuat akun demo ber-password bawaan, daftarkan akun seller di situs, ubah `YOUR_SELLER_EMAIL` pada [`demo_products.sql`](./database/demo_products.sql) ke email seller tersebut, lalu impor file itu saat database `main` dipilih.
- `database/seed.php` hanya untuk CLI dan membuat akun demo dengan kata sandi contoh. Jangan jalankan pada situs publik.
- Gunakan akun database khusus aplikasi, bukan `root`, dan jangan commit kredensial.
- Aktifkan HTTPS dan sediakan backup database, termasuk tabel `app_media`.
- Periksa batas dan kebijakan layanan eksternal, atribusi, privasi koordinat, serta sumber resmi harga/cuaca sebelum penggunaan produksi.
