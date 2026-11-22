<?php
require __DIR__ . '/includes/bootstrap.php';
require_login();

$account = current_user();
$sellerCanManage = can_access('sell');
$validStatuses = ['Menunggu', 'Diproses', 'Dikirim', 'Selesai'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $orderId = filter_var($_POST['order_id'] ?? null, FILTER_VALIDATE_INT);
    $status = (string) ($_POST['status'] ?? '');
    $shippingNote = trim((string) ($_POST['shipping_note'] ?? ''));
    if (!$sellerCanManage || !$orderId || !in_array($status, $validStatuses, true)
        || mb_strlen($shippingNote) > 255) {
        http_response_code(403);
        exit('Perubahan status pesanan tidak diizinkan.');
    }
    if ($status === 'Dikirim') {
        $stmt = db()->prepare('UPDATE orders SET status = ?, shipping_note = ? WHERE id = ? AND seller_id = ?');
        $stmt->execute([$status, $shippingNote ?: null, $orderId, $account['id']]);
    } else {
        $stmt = db()->prepare('UPDATE orders SET status = ? WHERE id = ? AND seller_id = ?');
        $stmt->execute([$status, $orderId, $account['id']]);
    }
    set_flash($stmt->rowCount() ? 'success' : 'error', $stmt->rowCount() ? 'Status pesanan berhasil diperbarui.' : 'Pesanan tidak ditemukan atau statusnya tidak berubah.');
    redirect('orders.php');
}

if ($account['role'] === 'admin') {
    $stmt = db()->prepare('SELECT o.*, b.name AS buyer_name, s.name AS seller_name FROM orders o JOIN users b ON b.id = o.buyer_id LEFT JOIN users s ON s.id = o.seller_id ORDER BY o.created_at DESC');
    $stmt->execute();
    $orders = $stmt->fetchAll();
} elseif ($sellerCanManage) {
    $stmt = db()->prepare('SELECT o.*, b.name AS buyer_name, s.name AS seller_name FROM orders o JOIN users b ON b.id = o.buyer_id LEFT JOIN users s ON s.id = o.seller_id WHERE o.buyer_id = ? OR o.seller_id = ? ORDER BY o.created_at DESC');
    $stmt->execute([$account['id'], $account['id']]);
    $orders = $stmt->fetchAll();
} else {
    $stmt = db()->prepare('SELECT o.*, b.name AS buyer_name, s.name AS seller_name FROM orders o JOIN users b ON b.id = o.buyer_id LEFT JOIN users s ON s.id = o.seller_id WHERE o.buyer_id = ? ORDER BY o.created_at DESC');
    $stmt->execute([$account['id']]);
    $orders = $stmt->fetchAll();
}

$pageTitle = 'Pesanan';
$activePage = 'orders';
require __DIR__ . '/includes/header.php';
?>
<section class="page-wrap orders-page">
    <div class="page-intro"><div class="section-kicker">Aktivitas niaga</div><h1>Pesanan<span class="greeting-dot">.</span></h1><p>Pantau pesanan masuk dan belanja komunitasmu.</p></div>
    <?php if (!$orders): ?>
        <div class="empty-state"><span class="empty-icon">↗</span><h2>Belum ada pesanan.</h2><p>Pesanan produk Niaga Tani akan tercatat di sini.</p><a class="button button-primary" href="marketplace.php">Jelajahi Niaga Tani</a></div>
    <?php else: ?>
        <div class="orders-table-wrap"><table class="data-table">
            <thead><tr><th>Produk</th><th>Pembeli</th><th>Penjual</th><th>Jumlah</th><th>Total</th><th>Status</th><th>Info pengiriman</th><th>Tanggal</th></tr></thead>
            <tbody><?php foreach ($orders as $order): ?>
                <tr>
                    <td><strong><?= e($order['product_name']) ?></strong></td>
                    <td><?= e($order['buyer_name']) ?></td><td><?= e($order['seller_name'] ?? 'Akun tidak tersedia') ?></td>
                    <td><?= number_format((int) $order['quantity']) ?></td><td><?= format_rupiah($order['total_price']) ?></td>
                    <td>
                        <?php if ($sellerCanManage && (int) $order['seller_id'] === (int) $account['id'] && $order['status'] !== 'Selesai'): ?>
                            <form method="post" class="status-form"><?= csrf_field() ?><input type="hidden" name="order_id" value="<?= (int) $order['id'] ?>">
                                <select name="status" aria-label="Status pesanan"><?php foreach (['Menunggu', 'Diproses', 'Dikirim', 'Selesai'] as $status): ?><option value="<?= e($status) ?>" <?= $order['status'] === $status ? 'selected' : '' ?>><?= e($status) ?></option><?php endforeach; ?></select>
                                <label class="shipping-note-field">Info pengiriman <small>Diisi saat status Dikirim · opsional</small><input name="shipping_note" maxlength="255" value="<?= e($order['shipping_note'] ?? '') ?>" placeholder="Kurir atau titik antar"></label>
                                <button class="button button-outline button-small" type="submit">Simpan status</button>
                            </form>
                        <?php else: ?><span class="status-pill status-<?= e(strtolower($order['status'])) ?>"><?= e($order['status']) ?></span><?php endif; ?>
                    </td>
                    <td><?= !empty($order['shipping_note']) ? e($order['shipping_note']) : '—' ?></td>
                    <td><?= e(date('d M Y', strtotime($order['created_at']))) ?></td>
                </tr>
            <?php endforeach; ?></tbody>
        </table></div>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
