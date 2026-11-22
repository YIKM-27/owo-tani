<?php
require __DIR__ . '/includes/bootstrap.php';
require_capability('land');

$account = current_user();
$landTypes = ['Sawah', 'Kebun', 'Ladang', 'Hidroponik', 'Lainnya'];
$activities = ['Penyiraman', 'Pemupukan', 'Penanganan cuaca ekstrem', 'Pengomposan'];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? '');
    if ($action === 'create' || $action === 'update') {
        $name = trim((string) ($_POST['name'] ?? ''));
        $type = (string) ($_POST['land_type'] ?? '');
        $area = filter_var($_POST['area_ha'] ?? null, FILTER_VALIDATE_FLOAT);
        $latitude = filter_var($_POST['latitude'] ?? null, FILTER_VALIDATE_FLOAT);
        $longitude = filter_var($_POST['longitude'] ?? null, FILTER_VALIDATE_FLOAT);
        $crop = trim((string) ($_POST['crop'] ?? ''));
        if ($name === '' || mb_strlen($name) > 120 || !in_array($type, $landTypes, true)
            || $area === false || $area <= 0 || $latitude === false || $latitude < -90 || $latitude > 90
            || $longitude === false || $longitude < -180 || $longitude > 180 || $crop === '' || mb_strlen($crop) > 100) {
            $error = 'Periksa kembali nama, tipe lahan, luas, koordinat, dan tanaman.';
        } elseif ($action === 'create') {
            db()->prepare('INSERT INTO farms (user_id, name, land_type, area_ha, latitude, longitude, crop) VALUES (?, ?, ?, ?, ?, ?, ?)')
                ->execute([$account['id'], $name, $type, $area, $latitude, $longitude, $crop]);
            set_flash('success', 'Lahan berhasil dicatat. Selamat merawat ruang tumbuhmu!');
            redirect('land.php');
        } else {
            $id = filter_var($_POST['farm_id'] ?? null, FILTER_VALIDATE_INT);
            $stmt = db()->prepare('UPDATE farms SET name = ?, land_type = ?, area_ha = ?, latitude = ?, longitude = ?, location_address = NULL, bmkg_adm4 = NULL, crop = ? WHERE id = ? AND user_id = ?');
            $stmt->execute([$name, $type, $area, $latitude, $longitude, $crop, $id, $account['id']]);
            if ($stmt->rowCount()) {
                set_flash('success', 'Informasi lahan berhasil diperbarui.');
                redirect('land.php');
            }
            $check = db()->prepare('SELECT id FROM farms WHERE id = ? AND user_id = ?');
            $check->execute([$id, $account['id']]);
            if (!$check->fetch()) {
                $error = 'Lahan tidak ditemukan.';
            } else {
                set_flash('success', 'Tidak ada perubahan pada informasi lahan.');
                redirect('land.php');
            }
        }
    } elseif ($action === 'delete') {
        $id = filter_var($_POST['farm_id'] ?? null, FILTER_VALIDATE_INT);
        $stmt = db()->prepare('DELETE FROM farms WHERE id = ? AND user_id = ?');
        $stmt->execute([$id, $account['id']]);
        set_flash($stmt->rowCount() ? 'success' : 'error', $stmt->rowCount() ? 'Lahan dan catatan aktivitasnya berhasil dihapus.' : 'Lahan tidak ditemukan.');
        redirect('land.php');
    } elseif ($action === 'log') {
        $farmId = filter_var($_POST['farm_id'] ?? null, FILTER_VALIDATE_INT);
        $activity = (string) ($_POST['activity'] ?? '');
        $note = trim((string) ($_POST['note'] ?? ''));
        $check = db()->prepare('SELECT id FROM farms WHERE id = ? AND user_id = ?');
        $check->execute([$farmId, $account['id']]);
        if (!$check->fetch() || !in_array($activity, $activities, true) || mb_strlen($note) > 255) {
            $error = 'Aktivitas tidak valid atau lahan tidak ditemukan.';
        } else {
            db()->prepare('INSERT INTO farm_logs (farm_id, activity, note) VALUES (?, ?, ?)')->execute([$farmId, $activity, $note ?: null]);
            set_flash('success', 'Catatan perawatan berhasil disimpan.');
            redirect('land.php');
        }
    } else {
        $error = 'Permintaan tidak dikenali.';
    }
}

$stmt = db()->prepare('SELECT * FROM farms WHERE user_id = ? ORDER BY created_at DESC');
$stmt->execute([$account['id']]);
$farms = $stmt->fetchAll();
$logsByFarm = [];
if ($farms) {
    $logQuery = db()->prepare('SELECT * FROM farm_logs WHERE farm_id = ? ORDER BY logged_at DESC LIMIT 5');
    foreach ($farms as $farm) {
        $logQuery->execute([$farm['id']]);
        $logsByFarm[$farm['id']] = $logQuery->fetchAll();
    }
}
$pageTitle = 'Lahanku';
$activePage = 'land';
require __DIR__ . '/includes/header.php';
?>
<section class="page-wrap land-page">
    <div class="page-intro page-intro-split">
        <div><div class="section-kicker">Ruang perawatanmu</div><h1>Lahanku<span class="greeting-dot">.</span></h1>
            <p>Catat setiap petak, rawat dengan teratur, dan simpan aktivitas tanimu.</p></div>
        <div class="land-total"><strong><?= count($farms) ?></strong><span>lahan tercatat</span></div>
    </div>
    <?php if ($error): ?><div class="form-alert" role="alert"><?= e($error) ?></div><?php endif; ?>
    <details class="panel farm-create-panel" id="tambah-lahan" <?= !$farms ? 'open' : '' ?>>
        <summary><span><span class="section-kicker">Mulai dari petak pertamamu</span><strong>Catat lahan baru</strong></span><span class="summary-plus">＋</span></summary>
        <form method="post" class="form-grid">
            <?= csrf_field() ?><input type="hidden" name="action" value="create">
            <label>Nama lahan<input name="name" maxlength="120" placeholder="Contoh: Sawah Keluarga" required></label>
            <label>Tipe lahan<select name="land_type" required><?php foreach ($landTypes as $type): ?><option value="<?= e($type) ?>"><?= e($type) ?></option><?php endforeach; ?></select></label>
            <label>Luas (hektare)<input type="number" name="area_ha" min="0.01" step="0.01" placeholder="1.25" required></label>
            <label>Tanaman utama<input name="crop" maxlength="100" placeholder="Contoh: Padi Ciherang" required></label>
            <label>Latitude<input type="number" name="latitude" min="-90" max="90" step="any" placeholder="-6.914744" required></label>
            <label>Longitude<input type="number" name="longitude" min="-180" max="180" step="any" placeholder="107.609810" required></label>
            <p class="field-wide coordinate-help">Cuaca akan dicari berdasarkan koordinat lahan. Isi titik di tengah petak untuk hasil lokasi yang lebih sesuai.</p>
            <div class="field-wide"><button class="button button-primary" type="submit">Simpan lahan <span aria-hidden="true">↗</span></button></div>
        </form>
    </details>

    <div class="land-list-heading"><div><div class="section-kicker">Catatan lapangan</div><h2>Petak yang kamu rawat.</h2></div></div>
    <?php if (!$farms): ?>
        <div class="empty-state"><span class="empty-icon">⌁</span><h2>Belum ada lahan tercatat.</h2><p>Mulai dengan mengisi nama lahan, luas, tanaman, dan titik koordinat di tengah petak.</p></div>
    <?php else: ?>
        <div class="farm-list">
            <?php foreach ($farms as $farm): ?>
                <article class="farm-card">
                    <div class="farm-card-main">
                        <div class="farm-card-top"><span class="category-pill"><?= e($farm['land_type']) ?></span><span class="farm-coordinate"><?= e(number_format((float) $farm['latitude'], 5)) ?>, <?= e(number_format((float) $farm['longitude'], 5)) ?></span></div>
                        <?php if (!empty($farm['location_address'])): ?><p class="farm-address"><?= e($farm['location_address']) ?></p><?php endif; ?>
                        <h3><?= e($farm['name']) ?></h3>
                        <div class="farm-facts"><span><strong><?= e(number_format((float) $farm['area_ha'], 2, ',', '.')) ?></strong> ha</span><span>Tanaman utama <strong><?= e($farm['crop']) ?></strong></span></div>
                        <div class="farm-actions">
                            <a class="button button-outline map-link" target="_blank" rel="noopener noreferrer" href="https://www.google.com/maps?q=<?= rawurlencode($farm['latitude'] . ',' . $farm['longitude']) ?>">Lihat lokasi di peta <span aria-hidden="true">↗</span></a>
                            <a class="button button-primary weather-link" href="weather.php?farm_id=<?= (int) $farm['id'] ?>">Lihat prakiraan cuaca <span aria-hidden="true">→</span></a>
                        </div>
                    </div>
                    <div class="farm-card-side">
                        <details class="farm-edit">
                            <summary>Edit informasi lahan</summary>
                            <form method="post" class="form-stack">
                                <?= csrf_field() ?><input type="hidden" name="action" value="update"><input type="hidden" name="farm_id" value="<?= (int) $farm['id'] ?>">
                                <label>Nama lahan<input name="name" value="<?= e($farm['name']) ?>" maxlength="120" required></label>
                                <label>Tipe lahan<select name="land_type"><?php foreach ($landTypes as $type): ?><option value="<?= e($type) ?>" <?= $farm['land_type'] === $type ? 'selected' : '' ?>><?= e($type) ?></option><?php endforeach; ?></select></label>
                                <label>Luas (hektare)<input type="number" name="area_ha" min="0.01" step="0.01" value="<?= e($farm['area_ha']) ?>" required></label>
                                <label>Tanaman utama<input name="crop" value="<?= e($farm['crop']) ?>" maxlength="100" required></label>
                                <div class="form-row"><label>Latitude<input type="number" name="latitude" min="-90" max="90" step="any" value="<?= e($farm['latitude']) ?>" required></label><label>Longitude<input type="number" name="longitude" min="-180" max="180" step="any" value="<?= e($farm['longitude']) ?>" required></label></div>
                                <button class="button button-primary button-small" type="submit">Simpan perubahan</button>
                            </form>
                        </details>
                        <form method="post" class="farm-delete" data-confirm="Hapus lahan ini beserta semua catatan perawatannya?">
                            <?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="farm_id" value="<?= (int) $farm['id'] ?>">
                            <button class="button button-quiet button-small" type="submit">Hapus lahan</button>
                        </form>
                    </div>
                    <div class="farm-activity">
                        <div class="activity-heading"><div><span class="section-kicker">Jurnal perawatan</span><h4>Perawatan terakhir</h4></div></div>
                        <?php if (empty($logsByFarm[$farm['id']])): ?><p class="muted-copy">Belum ada aktivitas. Catat penyiraman atau pemupukan di bawah.</p>
                        <?php else: ?><ul class="activity-list"><?php foreach ($logsByFarm[$farm['id']] as $log): ?><li><span class="activity-dot"></span><span><strong><?= e($log['activity']) ?></strong><?= $log['note'] ? ' · ' . e($log['note']) : '' ?><small><?= e(date('d M Y, H:i', strtotime($log['logged_at']))) ?></small></span></li><?php endforeach; ?></ul><?php endif; ?>
                        <form method="post" class="log-form">
                            <?= csrf_field() ?><input type="hidden" name="action" value="log"><input type="hidden" name="farm_id" value="<?= (int) $farm['id'] ?>">
                            <select name="activity" aria-label="Jenis aktivitas"><?php foreach ($activities as $activity): ?><option value="<?= e($activity) ?>"><?= e($activity) ?></option><?php endforeach; ?></select>
                            <input name="note" maxlength="255" placeholder="Catatan singkat (opsional)">
                            <button class="button button-outline button-small" type="submit">Catat</button>
                        </form>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
