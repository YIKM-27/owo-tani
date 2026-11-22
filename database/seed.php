<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Jalankan seeder dari terminal Laragon, bukan dari browser.\n");
}

$pdo = require __DIR__ . '/../config/database.php';
$accounts = [
    ['Admin OwO', 'admin@owotani.test', 'admin123', 'admin'],
    ['Petani Satu', 'petani1@owotani.test', 'Petani01', 'petani'],
    ['Petani Dua', 'petani2@owotani.test', 'Petani02', 'petani'],
    ['Penjual Satu', 'penjual1@owotani.test', 'Penjual01', 'penjual'],
    ['Penjual Dua', 'penjual2@owotani.test', 'Penjual02', 'penjual'],
    ['Tani Niaga Satu', 'taniniaga1@owotani.test', 'TaniJual01', 'petani_penjual'],
    ['Tani Niaga Dua', 'taniniaga2@owotani.test', 'TaniJual02', 'petani_penjual'],
    ['Warga Satu', 'warga1@owotani.test', 'Warga001', 'warga'],
    ['Warga Dua', 'warga2@owotani.test', 'Warga002', 'warga'],
];

$insertUser = $pdo->prepare('INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, ?)');
$findUser = $pdo->prepare('SELECT id FROM users WHERE email = ?');
foreach ($accounts as [$name, $email, $password, $role]) {
    $findUser->execute([$email]);
    if (!$findUser->fetch()) {
        $insertUser->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT), $role]);
    }
}

$findSeller = $pdo->prepare('SELECT id FROM users WHERE email = ?');
$findSeller->execute(['penjual1@owotani.test']);
$sellerId = (int) $findSeller->fetchColumn();
$products = [
    ['Benih padi Inpari 32', 'Benih', 'Benih pilihan dengan daya tumbuh baik, cocok untuk persiapan musim tanam berikutnya.', 78000, 'kg', 45],
    ['Pupuk kompos organik', 'Pupuk', 'Kompos matang dari bahan organik untuk membantu menjaga struktur dan kesuburan tanah.', 25000, 'karung', 32],
    ['Cangkul baja gagang kayu', 'Alat tani', 'Cangkul kuat untuk pengolahan lahan kebun dan sawah.', 92000, 'buah', 12],
    ['Beras pandan wangi', 'Hasil panen', 'Beras hasil panen lokal, diproses dengan hati-hati dan siap dimasak.', 18000, 'kg', 60],
    ['Bibit cabai rawit unggul', 'Benih', 'Bibit cabai sehat untuk budidaya di kebun atau pekarangan rumah.', 15000, 'pak', 28],
    ['Jagung pipilan kering', 'Hasil panen', 'Hasil panen petani lokal, sudah dikeringkan dan disortir.', 6200, 'kg', 150],
    ['Pupuk NPK seimbang', 'Pupuk', 'Pupuk majemuk untuk mendukung kebutuhan nutrisi tanaman sesuai dosis yang dianjurkan.', 145000, 'karung', 18],
    ['Sprayer tangan 16 liter', 'Alat tani', 'Tangki semprot praktis dengan tali punggung untuk perawatan tanaman.', 185000, 'buah', 9],
    ['Tomat merah segar', 'Hasil panen', 'Tomat segar hasil kebun petani, dipanen dan disortir untuk konsumsi harian.', 12000, 'kg', 35],
    ['Kentang Dieng pilihan', 'Hasil panen', 'Kentang pilihan dari dataran tinggi, cocok untuk aneka masakan rumahan.', 16000, 'kg', 45],
    ['Bawang merah Brebes', 'Hasil panen', 'Bawang merah lokal yang telah dikeringkan dan siap untuk kebutuhan dapur.', 28000, 'kg', 40],
    ['Singkong segar kebun', 'Hasil panen', 'Singkong segar dari kebun petani, tersedia untuk konsumsi atau olahan.', 4500, 'kg', 80],
    ['Tomat matang sortiran', 'Hasil panen sortiran', 'Tomat layak konsumsi dengan ukuran atau bentuk beragam, dijual lebih terjangkau agar tidak terbuang.', 7000, 'kg', 24, 'Lembang, Bandung Barat'],
    ['Wortel kecil sortiran', 'Hasil panen sortiran', 'Wortel segar dan layak konsumsi yang tidak lolos sortir ukuran pasar.', 6500, 'kg', 30, 'Dieng, Wonosobo'],
    ['Pisang kepok matang', 'Hasil panen sortiran', 'Pisang matang dengan tampilan beragam, cocok untuk konsumsi atau olahan rumahan.', 9000, 'sisir', 18, 'Banyuwangi'],
    ['Beras patah konsumsi', 'Hasil panen sortiran', 'Beras patah yang tetap layak konsumsi dan dapat digunakan untuk berbagai hidangan.', 12000, 'kg', 35, 'Klaten'],
];
$findProduct = $pdo->prepare('SELECT id FROM products WHERE seller_id = ? AND name = ?');
$insertProduct = $pdo->prepare('INSERT INTO products (seller_id, name, category, description, price, unit, stock, origin_area) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
foreach ($products as $product) {
    $findProduct->execute([$sellerId, $product[0]]);
    if (!$findProduct->fetch()) {
        $insertProduct->execute([
            $sellerId,
            $product[0],
            $product[1],
            $product[2],
            $product[3],
            $product[4],
            $product[5],
            $product[6] ?? null,
        ]);
    }
}

fwrite(STDOUT, "Seeder selesai. Kata sandi disimpan menggunakan password_hash().\n");
