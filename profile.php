<?php
require __DIR__ . '/includes/bootstrap.php';
require_login();

$account = current_user();
$error = '';
$uploadedAvatar = null;
$roles = ['petani', 'penjual', 'petani_penjual', 'warga'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $name = trim((string) ($_POST['name'] ?? ''));
    $role = (string) ($_POST['role'] ?? '');
    if ($account['role'] === 'admin') {
        $role = 'admin';
    }
    if (mb_strlen($name) < 2 || mb_strlen($name) > 100) {
        $error = 'Nama harus terdiri dari 2 sampai 100 karakter.';
    } elseif ($account['role'] !== 'admin' && !in_array($role, $roles, true)) {
        $error = 'Pilih peran yang tersedia.';
    } else {
        try {
            $uploadedAvatar = save_uploaded_image('avatar', 'profiles');
        } catch (RuntimeException $exception) {
            $error = $exception->getMessage();
        }
        if (!$error) {
            try {
                if ($uploadedAvatar) {
                    db()->prepare('UPDATE users SET name = ?, role = ?, avatar_path = ? WHERE id = ?')->execute([$name, $role, $uploadedAvatar, $account['id']]);
                } else {
                    db()->prepare('UPDATE users SET name = ?, role = ? WHERE id = ?')->execute([$name, $role, $account['id']]);
                }
            } catch (Throwable $exception) {
                delete_uploaded_image($uploadedAvatar);
                throw $exception;
            }
            $_SESSION['user']['name'] = $name;
            $_SESSION['user']['role'] = $role;
            if ($uploadedAvatar) {
                $_SESSION['user']['avatar_path'] = $uploadedAvatar;
                delete_uploaded_image($account['avatar_path'] ?? null);
            }
            set_flash('success', 'Profil berhasil diperbarui.');
            redirect('profile.php');
        }
    }
}

$pageTitle = 'Profil';
$activePage = 'profile';
require __DIR__ . '/includes/header.php';
?>
<section class="page-wrap profile-page">
    <div class="page-intro">
        <div class="section-kicker">Ruang personal</div><h1>Profilku.</h1>
        <p>Perbarui informasi dan pilih ruang yang sesuai dengan kegiatanmu saat ini.</p>
    </div>
    <div class="profile-layout">
        <aside class="profile-card">
            <div class="avatar avatar-large"><?php if (!empty($account['avatar_path'])): ?><img src="<?= e($account['avatar_path']) ?>" alt="Foto profil <?= e($account['name']) ?>"><?php else: ?><?= e(mb_substr($account['name'], 0, 1)) ?><?php endif; ?></div>
            <h2><?= e($account['name']) ?></h2>
            <p><?= e($account['email']) ?></p>
            <span class="role-pill"><?= e(role_label($account['role'])) ?></span>
            <?php if ($account['role'] === 'admin'): ?><small class="muted-copy">Akun administrator dikelola langsung melalui database.</small><?php endif; ?>
        </aside>
        <section class="panel profile-form-panel">
            <div class="section-kicker">Informasi akun</div><h2>Kenali dirimu.</h2>
            <?php if ($error): ?><div class="form-alert" role="alert"><?= e($error) ?></div><?php endif; ?>
            <form method="post" class="form-stack" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <label>Foto profil <small>JPG, PNG, atau WebP · maksimal 5 MB</small>
                    <input type="file" name="avatar" accept="image/jpeg,image/png,image/webp">
                </label>
                <label>Nama lengkap
                    <input type="text" name="name" value="<?= e($_POST['name'] ?? $account['name']) ?>" required minlength="2" maxlength="100">
                </label>
                <label>Email
                    <input type="email" value="<?= e($account['email']) ?>" disabled>
                    <small>Email belum dapat diubah dari halaman ini.</small>
                </label>
                <?php if ($account['role'] !== 'admin'): ?>
                    <label>Peran dalam komunitas
                        <select name="role" required>
                            <?php foreach ($roles as $availableRole): ?>
                                <option value="<?= e($availableRole) ?>" <?= $account['role'] === $availableRole ? 'selected' : '' ?>><?= e(role_label($availableRole)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <p class="role-hint">Akses fitur akan menyesuaikan peran yang dipilih. Produk dan lahan yang sudah disimpan tetap ada.</p>
                <?php endif; ?>
                <button class="button button-primary" type="submit">Simpan perubahan <span aria-hidden="true">↗</span></button>
            </form>
        </section>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
