<?php
require __DIR__ . '/includes/bootstrap.php';
require_login();

$account = current_user();
$isAdmin = $account['role'] === 'admin';
$counts = ['farms' => 0, 'products' => 0, 'orders' => 0];
if ($isAdmin) {
    $counts['farms'] = (int) db()->query('SELECT COUNT(*) FROM farms')->fetchColumn();
    $counts['products'] = (int) db()->query('SELECT COUNT(*) FROM products')->fetchColumn();
    $counts['orders'] = (int) db()->query('SELECT COUNT(*) FROM orders')->fetchColumn();
    $counts['users'] = (int) db()->query('SELECT COUNT(*) FROM users')->fetchColumn();
} else {
    if (can_access('land')) {
        $stmt = db()->prepare('SELECT COUNT(*) FROM farms WHERE user_id = ?');
        $stmt->execute([$account['id']]);
        $counts['farms'] = (int) $stmt->fetchColumn();
    }
    if (can_access('sell')) {
        $stmt = db()->prepare('SELECT COUNT(*) FROM products WHERE seller_id = ?');
        $stmt->execute([$account['id']]);
        $counts['products'] = (int) $stmt->fetchColumn();
    }
    $stmt = db()->prepare('SELECT COUNT(*) FROM orders WHERE buyer_id = ?');
    $stmt->execute([$account['id']]);
    $counts['orders'] = (int) $stmt->fetchColumn();
}

$roleMessages = [
    'petani' => $counts['farms'] ? 'Lahanmu sudah tercatat. Jaga ritme perawatan dan lihat pembaruan pasar.' : 'Mulai dengan mencatat lahanmu agar lebih mudah merencanakan perawatan.',
    'penjual' => $counts['products'] ? 'Katalogmu sudah mulai terisi. Pastikan stok dan informasi produk selalu terbaru.' : 'Tambahkan produk pertamamu dan kenalkan hasil terbaikmu kepada komunitas.',
    'petani_penjual' => 'Kelola lahan dan katalogmu dari satu tempat. Setiap langkah kecil membantu usahamu tumbuh.',
    'warga' => 'Temukan kebutuhan tani dan hasil panen pilihan dari para penjual di komunitas.',
];
$featuredProducts = [];
if (can_access('marketplace')) {
    $featuredProducts = db()->query(
        'SELECT p.id, p.name, p.category, p.image_path, p.price, p.unit, p.stock, u.name AS seller_name
         FROM products p JOIN users u ON u.id = p.seller_id
         WHERE p.stock > 0
         ORDER BY p.created_at DESC
         LIMIT 3'
    )->fetchAll();
}
$pageTitle = $isAdmin ? 'Dasbor admin' : 'Beranda';
$activePage = 'dashboard';
require __DIR__ . '/includes/header.php';
?>
<section class="dashboard-wrap page-wrap">
    <div class="dashboard-welcome">
        <div class="welcome-copy">
            <div class="section-kicker"><?= $isAdmin ? 'Ruang pengelola' : 'Ruang tumbuhmu' ?></div>
            <h1>Halo, <?= e($account['name']) ?><span class="greeting-dot">.</span></h1>
            <p class="welcome-role"><?= e(role_label($account['role'])) ?></p>
            <p class="welcome-message"><?= $isAdmin ? 'Pantau aktivitas OwO Tani dan jaga ruang ini tetap nyaman untuk semua.' : e($roleMessages[$account['role']] ?? 'Selamat datang di OwO Tani.') ?></p>
        </div>
        <div class="welcome-orbit" aria-hidden="true"><span>o</span><span>t</span><i></i></div>
    </div>

    <?php if ($isAdmin): ?>
        <div class="section-heading section-heading-dashboard">
            <div><div class="section-kicker">Ringkasan platform</div><h2>Semua tumbuh bersama.</h2></div>
            <span class="admin-badge">Administrator</span>
        </div>
        <div class="stat-grid stat-grid-four">
            <article class="stat-card"><span>Anggota</span><strong><?= number_format($counts['users']) ?></strong><small>Akun terdaftar</small></article>
            <article class="stat-card"><span>Lahan</span><strong><?= number_format($counts['farms']) ?></strong><small>Petak tercatat</small></article>
            <article class="stat-card"><span>Produk</span><strong><?= number_format($counts['products']) ?></strong><small>Di Niaga Tani</small></article>
            <article class="stat-card"><span>Pesanan</span><strong><?= number_format($counts['orders']) ?></strong><small>Aktivitas transaksi</small></article>
        </div>
        <section class="admin-panel">
            <div><div class="section-kicker">Pengelolaan</div><h2>Periksa ruang komunitas.</h2><p>Kelola produk dan pesanan melalui halaman Niaga Tani. Pembuatan akun admin hanya dapat dilakukan melalui database.</p></div>
            <a class="button button-primary" href="marketplace.php">Buka Niaga Tani <span aria-hidden="true">↗</span></a>
        </section>
    <?php else: ?>
        <div class="section-heading section-heading-dashboard">
            <div><div class="section-kicker">Menu utama</div><h2>Pilih yang ingin kamu buka.</h2></div>
            <a class="button button-outline dashboard-profile-button" href="profile.php">Atur profil <span aria-hidden="true">↗</span></a>
        </div>
        <div class="dashboard-cards">
            <?php if (can_access('land')): ?>
                <a class="dashboard-card dashboard-card-land" href="land.php">
                    <span class="dashboard-card-index">01</span><span class="dashboard-card-icon" aria-hidden="true">L</span>
                    <h3><?= $counts['farms'] ? 'Lahanmu tercatat' : 'Catat lahan pertamamu' ?></h3>
                    <p><?= $counts['farms'] ? number_format($counts['farms']) . ' petak siap dikelola dan dipantau.' : 'Simpan luas, lokasi, dan tanaman agar perawatannya makin terarah.' ?></p>
                    <span class="card-link"><?= $counts['farms'] ? 'Buka Lahanku' : 'Tambah lahan' ?> <span aria-hidden="true">→</span></span>
                </a>
            <?php endif; ?>
            <?php if (can_access('marketplace')): ?>
                <a class="dashboard-card dashboard-card-market" href="marketplace.php">
                    <span class="dashboard-card-index">02</span><span class="dashboard-card-icon" aria-hidden="true">N</span>
                    <h3><?= can_access('sell') ? 'Tumbuhkan niagamu' : 'Temukan kebutuhan tani' ?></h3>
                    <p><?= can_access('sell') ? 'Kelola produk dan jangkau lebih banyak pembeli.' : 'Jelajahi produk pilihan dari komunitas pertanian.' ?></p>
                    <span class="card-link">Buka Niaga Tani <span aria-hidden="true">→</span></span>
                </a>
            <?php endif; ?>
            <?php if (can_access('prices')): ?>
                <a class="dashboard-card dashboard-card-price" href="prices.php">
                    <span class="dashboard-card-index">03</span><span class="dashboard-card-icon" aria-hidden="true">H</span>
                    <h3>Baca arah harga</h3><p>Lihat gambaran harga komoditas dan tren beberapa waktu terakhir.</p>
                    <span class="card-link">Buka Harga Pasar <span aria-hidden="true">→</span></span>
                </a>
            <?php endif; ?>
            <a class="dashboard-card dashboard-card-orders" href="orders.php">
                <span class="dashboard-card-index">04</span><span class="dashboard-card-icon" aria-hidden="true">P</span>
                <h3><?= can_access('sell') ? 'Aktivitas pesanan' : 'Riwayat belanja' ?></h3>
                <p><?= number_format($counts['orders']) ?> pesanan tercatat di akunmu.</p>
                <span class="card-link">Buka Pesanan <span aria-hidden="true">→</span></span>
            </a>
        </div>
        <div class="dashboard-note"><span class="note-mark">i</span><p><?= e($roleMessages[$account['role']] ?? '') ?> <a href="profile.php">Ubah peranmu dari halaman profil.</a></p></div>
        <?php if (can_access('marketplace')): ?>
            <section class="dashboard-featured" aria-labelledby="featured-products-title">
                <div class="featured-heading">
                    <div><div class="section-kicker">Dari Niaga Tani</div><h2 id="featured-products-title">Pilihan untuk hari ini.</h2><p>Produk pertanian dari penjual di komunitas.</p></div>
                    <a class="button button-outline" href="marketplace.php">Lihat semua produk <span aria-hidden="true">→</span></a>
                </div>
                <?php if ($featuredProducts): ?>
                    <div class="featured-product-grid">
                        <?php foreach ($featuredProducts as $product): ?>
                            <a class="featured-product" href="marketplace.php">
                                <span class="featured-product-image">
                                    <img src="<?= e(product_image_url($product)) ?>" alt="" loading="lazy" data-product-image>
                                    <span class="category-pill"><?= e($product['category']) ?></span>
                                </span>
                                <span class="featured-product-info"><span class="featured-product-seller"><?= e($product['seller_name']) ?></span><strong><?= e($product['name']) ?></strong><span class="featured-product-price"><?= format_rupiah($product['price']) ?><small> / <?= e($product['unit']) ?></small></span></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="featured-empty"><p>Produk unggulan akan tampil di sini setelah penjual menambahkan produk.</p><a class="text-link" href="marketplace.php">Jelajahi Niaga Tani <span aria-hidden="true">→</span></a></div>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
