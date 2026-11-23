SET @demo_seller_email = 'YOUR_SELLER_EMAIL';
SET @demo_seller_id = (
    SELECT id
    FROM users
    WHERE BINARY email = BINARY @demo_seller_email
      AND role IN ('penjual', 'petani_penjual')
    LIMIT 1
);

SELECT IF(
    @demo_seller_id IS NULL,
    'Akun penjual tidak ditemukan. Daftarkan akun penjual dan isi emailnya di file ini.',
    CONCAT('Produk contoh akan ditautkan ke akun penjual id ', @demo_seller_id, '.')
) AS import_status;

INSERT INTO products (seller_id, name, category, description, origin_area, price, unit, stock)
SELECT @demo_seller_id, sample.name, sample.category, sample.description, sample.origin_area,
       sample.price, sample.unit, sample.stock
FROM (
    SELECT 'Benih padi Inpari 32' AS name, 'Benih' AS category,
           'Benih pilihan dengan daya tumbuh baik, cocok untuk persiapan musim tanam berikutnya.' AS description,
           NULL AS origin_area, 78000 AS price, 'kg' AS unit, 45 AS stock
    UNION ALL SELECT 'Pupuk kompos organik', 'Pupuk',
           'Kompos matang dari bahan organik untuk membantu menjaga struktur dan kesuburan tanah.',
           NULL, 25000, 'karung', 32
    UNION ALL SELECT 'Cangkul baja gagang kayu', 'Alat tani',
           'Cangkul kuat untuk pengolahan lahan kebun dan sawah.',
           NULL, 92000, 'buah', 12
    UNION ALL SELECT 'Beras pandan wangi', 'Hasil panen',
           'Beras hasil panen lokal, diproses dengan hati-hati dan siap dimasak.',
           NULL, 18000, 'kg', 60
    UNION ALL SELECT 'Bibit cabai rawit unggul', 'Benih',
           'Bibit cabai sehat untuk budidaya di kebun atau pekarangan rumah.',
           NULL, 15000, 'pak', 28
    UNION ALL SELECT 'Jagung pipilan kering', 'Hasil panen',
           'Hasil panen petani lokal, sudah dikeringkan dan disortir.',
           NULL, 6200, 'kg', 150
    UNION ALL SELECT 'Pupuk NPK seimbang', 'Pupuk',
           'Pupuk majemuk untuk mendukung kebutuhan nutrisi tanaman sesuai dosis yang dianjurkan.',
           NULL, 145000, 'karung', 18
    UNION ALL SELECT 'Sprayer tangan 16 liter', 'Alat tani',
           'Tangki semprot praktis dengan tali punggung untuk perawatan tanaman.',
           NULL, 185000, 'buah', 9
    UNION ALL SELECT 'Tomat merah segar', 'Hasil panen',
           'Tomat segar hasil kebun petani, dipanen dan disortir untuk konsumsi harian.',
           NULL, 12000, 'kg', 35
    UNION ALL SELECT 'Kentang Dieng pilihan', 'Hasil panen',
           'Kentang pilihan dari dataran tinggi, cocok untuk aneka masakan rumahan.',
           NULL, 16000, 'kg', 45
    UNION ALL SELECT 'Bawang merah Brebes', 'Hasil panen',
           'Bawang merah lokal yang telah dikeringkan dan siap untuk kebutuhan dapur.',
           NULL, 28000, 'kg', 40
    UNION ALL SELECT 'Singkong segar kebun', 'Hasil panen',
           'Singkong segar dari kebun petani, tersedia untuk konsumsi atau olahan.',
           NULL, 4500, 'kg', 80
    UNION ALL SELECT 'Tomat matang sortiran', 'Hasil panen sortiran',
           'Tomat layak konsumsi dengan tampilan beragam, dijual lebih terjangkau agar tidak terbuang.',
           'Lembang, Bandung Barat', 7000, 'kg', 24
    UNION ALL SELECT 'Wortel kecil sortiran', 'Hasil panen sortiran',
           'Wortel segar dan layak konsumsi yang tidak lolos sortir ukuran pasar.',
           'Dieng, Wonosobo', 6500, 'kg', 30
    UNION ALL SELECT 'Pisang kepok matang', 'Hasil panen sortiran',
           'Pisang matang dengan tampilan beragam, cocok untuk konsumsi atau olahan rumahan.',
           'Banyuwangi', 9000, 'sisir', 18
    UNION ALL SELECT 'Beras patah konsumsi', 'Hasil panen sortiran',
           'Beras patah yang tetap layak konsumsi dan dapat digunakan untuk berbagai hidangan.',
           'Klaten', 12000, 'kg', 35
) AS sample
WHERE @demo_seller_id IS NOT NULL
  AND NOT EXISTS (
      SELECT 1
      FROM products existing
      WHERE existing.seller_id = @demo_seller_id
        AND BINARY existing.name = BINARY sample.name
  );
