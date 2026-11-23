<?php
require __DIR__ . '/includes/bootstrap.php';

if (current_user()) {
    redirect('dashboard.php');
}

$error = '';
$email = $_COOKIE['owo_tani_email'] ?? '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');
    try {
        $statement = db()->prepare('SELECT id, name, email, password_hash, role, avatar_path FROM users WHERE email = ? LIMIT 1');
        $statement->execute([$email]);
        $account = $statement->fetch();
        if ($account && password_verify($password, $account['password_hash'])) {
            session_regenerate_id(true);
            unset($account['password_hash']);
            $_SESSION['user'] = $account;
            if (isset($_POST['remember_email'])) {
                setcookie('owo_tani_email', $email, [
                    'expires' => time() + 60 * 60 * 24 * 30,
                    'httponly' => true,
                    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
                    'samesite' => 'Lax',
                ]);
            } else {
                setcookie('owo_tani_email', '', time() - 3600, '/');
            }
            redirect('dashboard.php');
        }
        $error = 'Email atau kata sandi belum sesuai. Periksa kembali, ya.';
    } catch (PDOException) {
        error_log('OwO Tani login database error');
        $error = 'Layanan database belum dapat dihubungi. Silakan coba beberapa saat lagi.';
    }
}

$pageTitle = 'Masuk';
require __DIR__ . '/includes/header.php';
?>
<section class="auth-page">
    <div class="auth-aside auth-aside-login">
        <div class="auth-aside-content">
            <div class="section-kicker section-kicker-light">Senang bertemu kembali</div>
            <h1>Setiap musim punya ceritanya.</h1>
            <p>Lanjutkan merawat hal-hal baik yang sudah kamu mulai.</p>
            <div class="auth-aside-bottom"><span class="status-dot"></span> Ruang tumbuh untuk pertanian Indonesia</div>
        </div>
    </div>
    <div class="auth-panel">
        <div class="auth-form-wrap">
            <div class="section-kicker">Akun OwO Tani</div>
            <h2>Selamat datang.</h2>
            <p class="muted-copy">Masuk untuk melanjutkan perjalanan tanimu.</p>
            <?php if ($error): ?><div class="form-alert" role="alert"><?= e($error) ?></div><?php endif; ?>
            <form method="post" class="form-stack">
                <?= csrf_field() ?>
                <label>Email
                    <input type="email" name="email" autocomplete="email" value="<?= e($email) ?>" placeholder="nama@email.com" required>
                </label>
                <label>Kata sandi
                    <input type="password" name="password" autocomplete="current-password" placeholder="Masukkan kata sandi" required>
                </label>
                <label class="checkbox-label">
                    <input type="checkbox" name="remember_email" value="1" <?= isset($_COOKIE['owo_tani_email']) ? 'checked' : '' ?>>
                    <span>Ingat email di perangkat ini</span>
                </label>
                <button class="button button-primary button-wide" type="submit">Masuk ke akun <span aria-hidden="true">↗</span></button>
            </form>
            <p class="auth-switch">Belum punya akun? <a href="register.php">Buat akun baru</a></p>
        </div>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
