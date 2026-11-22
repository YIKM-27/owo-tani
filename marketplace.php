<?php
require __DIR__ . '/includes/bootstrap.php';
require_capability('marketplace');

$account = current_user();
$categories = ['Benih', 'Pupuk', 'Alat tani', 'Hasil panen', 'Hasil panen sortiran', 'Lainnya'];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? '');
    if ($action === 'create' || $action === 'update') {
        if (!can_access('sell')) {
            http_response_code(403);
            exit('Peran akun ini tidak memiliki akses untuk mengelola produk.');
        }
        $name = trim((string) ($_POST['name'] ?? ''));
        $category = (string) ($_POST['category'] ?? '');
        $description = trim((string) ($_POST['description'] ?? ''));
        $originArea = trim((string) ($_POST['origin_area'] ?? ''));
        $price = filter_var($_POST['price'] ?? null, FILTER_VALIDATE_FLOAT);
        $unit = trim((string) ($_POST['unit'] ?? ''));
        $stock = filter_var($_POST['stock'] ?? null, FILTER_VALIDATE_INT);
        $uploadedImage = null;
        if ($name === '' || mb_strlen($name) > 140 || !in_array($category, $categories, true)
            || $description === '' || mb_strlen($description) > 3000 || mb_strlen($originArea) > 120 || $price === false
            || $price <= 0 || $unit === '' || mb_strlen($unit) > 40 || $stock === false || $stock < 0) {
            $error = 'Lengkapi informasi produk dengan benar. Harga harus lebih dari nol dan stok tidak boleh negatif.';
        } elseif ($action === 'create') {
            try {
                $uploadedImage = save_uploaded_image('image', 'products');
            } catch (RuntimeException $exception) {
                $error = $exception->getMessage();
            }
            if (!$error) {
                try {
                    $stmt = db()->prepare('INSERT INTO products (seller_id, name, category, description, image_path, origin_area, price, unit, stock) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
                    $stmt->execute([$account['id'], $name, $category, $description, $uploadedImage, $originArea ?: null, $price, $unit, $stock]);
                } catch (Throwable $exception) {
                    delete_uploaded_image($uploadedImage);
                    throw $exception;
                }
                set_flash('success', 'Produk berhasil ditambahkan ke Niaga Tani.');
                redirect('marketplace.php');
            }
        } else {
            $id = filter_var($_POST['product_id'] ?? null, FILTER_VALIDATE_INT);
            $stmt = db()->prepare('SELECT seller_id, image_path FROM products WHERE id = ?');
            $stmt->execute([$id]);
            $product = $stmt->fetch();
            if (!$product || ((int) $product['seller_id'] !== (int) $account['id'] && $account['role'] !== 'admin')) {
                $error = 'Produk tidak ditemukan atau bukan milik akunmu.';
            } else {
                try {
                    $uploadedImage = save_uploaded_image('image', 'products');
                } catch (RuntimeException $exception) {
                    $error = $exception->getMessage();
                }
                if (!$error) {
                    try {
                        if ($uploadedImage) {
                            $stmt = db()->prepare('UPDATE products SET name = ?, category = ?, description = ?, image_path = ?, origin_area = ?, price = ?, unit = ?, stock = ? WHERE id = ?');
                            $stmt->execute([$name, $category, $description, $uploadedImage, $originArea ?: null, $price, $unit, $stock, $id]);
                        } else {
                            $stmt = db()->prepare('UPDATE products SET name = ?, category = ?, description = ?, origin_area = ?, price = ?, unit = ?, stock = ? WHERE id = ?');
                            $stmt->execute([$name, $category, $description, $originArea ?: null, $price, $unit, $stock, $id]);
                        }
                    } catch (Throwable $exception) {
                        delete_uploaded_image($uploadedImage);
                        throw $exception;
                    }
                    if ($uploadedImage) {
                        delete_uploaded_image($product['image_path'] ?? null);
                    }
                    set_flash('success', 'Informasi produk berhasil diperbarui.');
                    redirect('marketplace.php');
                }
            }
        }
    } elseif ($action === 'delete') {
        if (!can_access('sell')) {
            http_response_code(403);
            exit('Peran akun ini tidak memiliki akses untuk mengelola produk.');
        }
        $id = filter_var($_POST['product_id'] ?? null, FILTER_VALIDATE_INT);
        $stmt = db()->prepare('SELECT seller_id, image_path FROM products WHERE id = ?');
        $stmt->execute([$id]);
        $product = $stmt->fetch();
        if ($product && ((int) $product['seller_id'] === (int) $account['id'] || $account['role'] === 'admin')) {
            db()->prepare('DELETE FROM products WHERE id = ?')->execute([$id]);
            delete_uploaded_image($product['image_path'] ?? null);
            set_flash('success', 'Produk berhasil dihapus.');
            redirect('marketplace.php');
        }
        $error = 'Produk tidak ditemukan atau bukan milik akunmu.';
    } elseif ($action === 'buy') {
        $productId = filter_var($_POST['product_id'] ?? null, FILTER_VALIDATE_INT);
        $quantity = filter_var($_POST['quantity'] ?? null, FILTER_VALIDATE_INT);
        if (!$productId || !$quantity || $quantity < 1 || $quantity > 9999) {
            $error = 'Jumlah pesanan tidak valid.';
        } else {
            try {
                $pdo = db();
                $pdo->beginTransaction();
                $stmt = $pdo->prepare('SELECT id, seller_id, name, price, unit, stock FROM products WHERE id = ? FOR UPDATE');
                $stmt->execute([$productId]);
                $product = $stmt->fetch();
                if (!$product || (int) $product['stock'] < $quantity) {
                    $pdo->rollBack();
                    $error = 'Stok produk tidak mencukupi. Silakan periksa stok terbaru.';
                } elseif ((int) $product['seller_id'] === (int) $account['id']) {
                    $pdo->rollBack();
                    $error = 'Kamu tidak dapat memesan produk milik akun sendiri.';
                } else {
                    $total = (float) $product['price'] * $quantity;
                    $pdo->prepare('UPDATE products SET stock = stock - ? WHERE id = ?')->execute([$quantity, $productId]);
                    $pdo->prepare('INSERT INTO orders (product_id, buyer_id, seller_id, product_name, quantity, unit_price, total_price) VALUES (?, ?, ?, ?, ?, ?, ?)')
                        ->execute([$productId, $account['id'], $product['seller_id'], $product['name'], $quantity, $product['price'], $total]);
                    $pdo->commit();
                    set_flash('success', 'Pesanan berhasil dibuat. Penjual akan menindaklanjutinya.');
                    redirect('orders.php');
                }
            } catch (Throwable $exception) {
                if (db()->inTransaction()) {
                    db()->rollBack();
                }
                throw $exception;
            }
        }
    } else {
        $error = 'Permintaan tidak dikenali.';
    }
}

$search = trim((string) ($_GET['q'] ?? ''));
$categoryFilter = (string) ($_GET['category'] ?? '');
$sql = 'SELECT p.*, u.name AS seller_name FROM products p JOIN users u ON u.id = p.seller_id WHERE 1=1';
$params = [];
if ($search !== '') {
    $sql .= ' AND (p.name LIKE ? OR p.description LIKE ?)';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}
if (in_array($categoryFilter, $categories, true)) {
    $sql .= ' AND p.category = ?';
    $params[] = $categoryFilter;
}
$sql .= ' ORDER BY p.created_at DESC';
$stmt = db()->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();
$pageTitle = 'Niaga Tani';
$activePage = 'marketplace';
require __DIR__ . '/includes/header.php';
?>
<section class="page-wrap marketplace-page">
    <div class="page-intro page-intro-split">
        <div><div class="section-kicker">Pasar komunitas</div><h1>Niaga Tani<span class="greeting-dot">.</span></h1>
            <p>Temukan kebutuhan budidaya dan hasil panen dari sesama pelaku tani.</p></div>
        <?php if (can_access('sell')): ?>
            <a class="button button-primary" href="#tambah-produk">Tambah produk <span aria-hidden="true">＋</span></a>
        <?php endif; ?>
    </div>
    <?php if ($error): ?><div class="form-alert" role="alert"><?= e($error) ?></div><?php endif; ?>

    <?php if (can_access('sell')): ?>
        <details class="panel product-create-panel" id="tambah-produk">
            <summary><span><span class="section-kicker">Ruang jualmu</span><strong>Tambahkan produk baru</strong></span><span class="summary-plus">＋</span></summary>
            <form method="post" class="form-grid" enctype="multipart/form-data">
                <?= csrf_field() ?><input type="hidden" name="action" value="create">
                <label>Nama produk<input name="name" maxlength="140" placeholder="Contoh: Beras organik pandan wangi" required></label>
                <label>Kategori<select name="category" required><?php foreach ($categories as $category): ?><option value="<?= e($category) ?>"><?= e($category) ?></option><?php endforeach; ?></select></label>
                <label>Harga per satuan<input type="number" name="price" min="1" step="100" placeholder="25000" required></label>
                <label>Satuan jual<input name="unit" maxlength="40" placeholder="kg, karung, ikat" required></label>
                <label>Stok tersedia<input type="number" name="stock" min="0" step="1" value="1" required></label>
                <label>Foto produk <small>JPG, PNG, atau WebP · maks. 5 MB</small><input type="file" name="image" accept="image/jpeg,image/png,image/webp"></label>
                <label>Asal daerah <small>Opsional · maks. 120 karakter</small><input name="origin_area" maxlength="120" placeholder="Contoh: Lembang, Bandung"></label>
                <label class="field-wide">Cerita singkat produk<textarea name="description" rows="3" maxlength="3000" placeholder="Ceritakan kualitas, asal, atau keunggulan produk." required></textarea></label>
                <div class="field-wide"><button class="button button-primary" type="submit">Terbitkan produk <span aria-hidden="true">↗</span></button></div>
            </form>
        </details>
    <?php endif; ?>

    <form class="market-filters" method="get">
        <label class="search-field"><span aria-hidden="true">⌕</span><input type="search" name="q" value="<?= e($search) ?>" placeholder="Cari benih, pupuk, hasil panen..."><button type="submit">Cari</button></label>
        <select name="category" aria-label="Filter kategori" data-auto-submit>
            <option value="">Semua kategori</option>
            <?php foreach ($categories as $category): ?><option value="<?= e($category) ?>" <?= $categoryFilter === $category ? 'selected' : '' ?>><?= e($category) ?></option><?php endforeach; ?>
        </select>
    </form>
    <div class="market-results-heading"><div><div class="section-kicker">Pilihan komunitas</div><h2>Produk untuk tumbuh lebih baik.</h2></div><span><?= count($products) ?> produk</span></div>

    <?php if (!$products): ?>
        <div class="empty-state"><span class="empty-icon">⌁</span><h2>Belum ada produk di sini.</h2><p><?= $search || $categoryFilter ? 'Coba ubah kata pencarian atau kategori.' : 'Produk yang ditambahkan penjual akan muncul di halaman ini.' ?></p></div>
    <?php else: ?>
        <div class="product-grid">
            <?php foreach ($products as $index => $product): ?>
                <?php $isOwner = (int) $product['seller_id'] === (int) $account['id']; ?>
                <article class="product-card">
                    <div class="product-visual product-visual-<?= $index % 4 ?>">
                        <img src="<?= e(product_image_url($product)) ?>" alt="<?= e($product['name']) ?>" loading="lazy" data-product-image>
                        <span class="product-visual-mark" aria-hidden="true"><?= ['✳', '⌁', '↗', '○'][$index % 4] ?></span>
                        <span class="category-pill"><?= e($product['category']) ?></span>
                    </div>
                    <div class="product-card-body">
                        <div class="product-meta"><span>Oleh <?= e($product['seller_name']) ?></span><?php if ($isOwner): ?><span class="owner-pill">Produkmu</span><?php endif; ?></div>
                        <?php if (!empty($product['origin_area'])): ?><p class="product-origin">Produk lokal: <?= e($product['origin_area']) ?></p><?php endif; ?>
                        <h3><?= e($product['name']) ?></h3>
                        <p class="product-description"><?= e($product['description']) ?></p>
                        <div class="product-price"><?= format_rupiah($product['price']) ?><small> / <?= e($product['unit']) ?></small></div>
                        <div class="product-stock"><?= (int) $product['stock'] > 0 ? 'Tersedia ' . number_format((int) $product['stock']) . ' ' . e($product['unit']) : 'Stok habis' ?></div>
                        <?php if (can_access('sell') && ($isOwner || $account['role'] === 'admin')): ?>
                            <details class="product-edit">
                                <summary>Edit produk</summary>
                                <form method="post" class="form-stack" enctype="multipart/form-data">
                                    <?= csrf_field() ?><input type="hidden" name="action" value="update"><input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
                                    <label>Nama produk<input name="name" value="<?= e($product['name']) ?>" maxlength="140" required></label>
                                    <label>Kategori<select name="category"><?php foreach ($categories as $category): ?><option value="<?= e($category) ?>" <?= $product['category'] === $category ? 'selected' : '' ?>><?= e($category) ?></option><?php endforeach; ?></select></label>
                                    <label>Deskripsi<textarea name="description" rows="3" maxlength="3000" required><?= e($product['description']) ?></textarea></label>
                                    <label>Asal daerah <small>Opsional · maks. 120 karakter</small><input name="origin_area" value="<?= e($product['origin_area'] ?? '') ?>" maxlength="120"></label>
                                    <label>Ganti foto produk <small>Opsional · JPG, PNG, atau WebP · maks. 5 MB</small><input type="file" name="image" accept="image/jpeg,image/png,image/webp"></label>
                                    <div class="form-row"><label>Harga<input type="number" name="price" min="1" step="100" value="<?= e($product['price']) ?>" required></label><label>Satuan<input name="unit" value="<?= e($product['unit']) ?>" required></label></div>
                                    <label>Stok<input type="number" name="stock" min="0" value="<?= (int) $product['stock'] ?>" required></label>
                                    <button class="button button-primary button-small" type="submit">Simpan</button>
                                </form>
                            </details>
                            <form method="post" class="product-delete" data-confirm="Hapus produk ini dari Niaga Tani?">
                                <?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
                                <button class="button button-quiet button-small" type="submit">Hapus produk</button>
                            </form>
                        <?php elseif (!$isOwner && (int) $product['stock'] > 0): ?>
                            <form method="post" class="buy-form">
                                <?= csrf_field() ?><input type="hidden" name="action" value="buy"><input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
                                <label><span class="sr-only">Jumlah yang dipesan</span><input aria-label="Jumlah yang dipesan" type="number" name="quantity" min="1" max="<?= (int) $product['stock'] ?>" value="1" required></label>
                                <button class="button button-primary button-small" type="submit">Pesan produk <span aria-hidden="true">↗</span></button>
                            </form>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
