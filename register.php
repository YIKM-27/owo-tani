<?php
require __DIR__ . '/includes/bootstrap.php';

if (current_user()) {
    redirect('dashboard.php');
}

$error = '';
$name = '';
$email = '';
$role = 'petani';
$roles = ['petani', 'penjual', 'petani_penjual', 'warga'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $name = trim((string) ($_POST['name'] ?? ''));
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $role = (string) ($_POST['role'] ?? '');
    $password = (string) ($_POST['password'] ?? '');
    $confirmation = (string) ($_POST['password_confirmation'] ?? '');

    if (mb_strlen($name) < 2 || mb_strlen($name) > 100) {
        $error = 'Nama harus terdiri dari 2 sampai 100 karakter.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) {
        $error = 'Masukkan alamat email yang valid.';
    } elseif (!in_array($role, $roles, true)) {
        $error = 'Pilih peran yang tersedia.';
    } elseif (strlen($password) < 8) {
        $error = 'Kata sandi minimal terdiri dari 8 karakter.';
    } elseif ($password !== $confirmation) {
        $error = 'Konfirmasi kata sandi belum sama.';
    } else {
        try {
            $check = db()->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
            $check->execute([$email]);
            if ($check->fetch()) {
                $error = 'Email tersebut sudah terdaftar. Silakan masuk atau gunakan email lain.';
            } else {
                $insert = db()->prepare('INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, ?)');
                $insert->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT), $role]);
                $userId = (int) db()->lastInsertId();
                session_regenerate_id(true);
                $_SESSION['user'] = [
                    'id' => $userId,
                    'name' => $name,
                    'email' => $email,
                    'role' => $role,
                ];
                set_flash('success', 'Akunmu sudah siap. Selamat datang di PekaTani!');
                redirect('dashboard.php');
            }
        } catch (PDOException $exception) {
            error_log('PekaTani registration database error: ' . $exception->getCode());
            if ((string) $exception->getCode() === '23000') {
                $error = 'Email tersebut sudah terdaftar. Silakan masuk atau gunakan email lain.';
            } else {
                $error = 'Akun belum berhasil dibuat karena ada kendala database. Coba lagi nanti.';
            }
        }
    }
}

$pageTitle = 'Buat akun';
require __DIR__ . '/includes/header.php';
?>
<section class="auth-page auth-page-register">
    <div class="auth-aside auth-aside-register">
        <div class="auth-aside-content">
            <div class="section-kicker section-kicker-light">Tempat baru untuk bertumbuh</div>
            <h1>Perjalanan baik dimulai dari sini.</h1>
            <p>Pilih peran yang paling dekat dengan keseharianmu. Kamu bisa mengubahnya kapan saja dari profil.</p>
            <div class="auth-aside-bottom"><span class="status-dot"></span> Satu akun, akses sesuai peranmu</div>
        </div>
    </div>
    <div class="auth-panel">
        <div class="auth-form-wrap">
            <div class="section-kicker">Bergabung bersama kami</div>
            <h2>Buat akun baru.</h2>
            <p class="muted-copy">Ceritakan sedikit tentang dirimu untuk memulai.</p>
            <?php if ($error): ?><div class="form-alert" role="alert"><?= e($error) ?></div><?php endif; ?>
            <form method="post" class="form-stack">
                <?= csrf_field() ?>
                <label>Nama lengkap
                    <input type="text" name="name" autocomplete="name" value="<?= e($name) ?>" placeholder="Nama yang ingin kami panggil" required minlength="2" maxlength="100">
                </label>
                <label>Email
                    <input type="email" name="email" autocomplete="email" value="<?= e($email) ?>" placeholder="nama@email.com" required>
                </label>
                <label>Peranmu
                    <select name="role" data-role-select required>
                        <option value="petani" <?= $role === 'petani' ? 'selected' : '' ?>>Petani</option>
                        <option value="penjual" <?= $role === 'penjual' ? 'selected' : '' ?>>Penjual</option>
                        <option value="petani_penjual" <?= $role === 'petani_penjual' ? 'selected' : '' ?>>Petani dan Penjual</option>
                        <option value="warga" <?= $role === 'warga' ? 'selected' : '' ?>>Warga / Wargi</option>
                    </select>
                </label>
                <div class="role-hint" data-role-hint>Kelola lahan, jelajahi Niaga Tani, dan pantau harga pasar.</div>
                <div class="form-row">
                    <label>Kata sandi
                        <input type="password" name="password" autocomplete="new-password" placeholder="Minimal 8 karakter" required minlength="8">
                    </label>
                    <label>Ulangi kata sandi
                        <input type="password" name="password_confirmation" autocomplete="new-password" placeholder="Ketik ulang" required minlength="8">
                    </label>
                </div>
                <button class="button button-primary button-wide" type="submit">Buat akun PekaTani <span aria-hidden="true">↗</span></button>
            </form>
            <p class="auth-switch">Sudah punya akun? <a href="login.php">Masuk di sini</a></p>
        </div>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
