<?php
$pageTitle = $pageTitle ?? 'PekaTani';
$activePage = $activePage ?? '';
$loggedInUser = current_user();
$flashMessage = take_flash();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#183c32">
    <title><?= e($pageTitle) ?> · PekaTani</title>
    <link rel="icon" type="image/svg+xml" href="assets/favicon.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/app.css">
    <script src="assets/js/app.js" defer></script>
</head>
<body>
<header class="site-header">
    <a class="brand" href="<?= $loggedInUser ? 'dashboard.php' : 'index.php' ?>" aria-label="PekaTani, beranda">
        <span class="brand-mark" aria-hidden="true">p</span>
        <span>Peka<strong>Tani</strong></span>
    </a>
    <button class="nav-toggle" type="button" aria-label="Buka navigasi" aria-expanded="false" data-nav-toggle>
        <span></span><span></span><span></span>
    </button>
    <nav class="main-nav" data-main-nav>
        <?php if ($loggedInUser): ?>
            <a class="<?= $activePage === 'dashboard' ? 'is-active' : '' ?>" href="dashboard.php">Beranda</a>
            <?php if (can_access('marketplace')): ?>
                <a class="<?= $activePage === 'marketplace' ? 'is-active' : '' ?>" href="marketplace.php">Niaga Tani</a>
            <?php endif; ?>
            <?php if (can_access('land')): ?>
                <a class="<?= $activePage === 'land' ? 'is-active' : '' ?>" href="land.php">Lahanku</a>
            <?php endif; ?>
            <?php if (can_access('prices')): ?>
                <a class="<?= $activePage === 'prices' ? 'is-active' : '' ?>" href="prices.php">Harga Pasar</a>
            <?php endif; ?>
            <a class="<?= $activePage === 'orders' ? 'is-active' : '' ?>" href="orders.php">Pesanan</a>
            <a class="nav-profile <?= $activePage === 'profile' ? 'is-active' : '' ?>" href="profile.php">
                <span class="avatar avatar-small"><?php if (!empty($loggedInUser['avatar_path'])): ?><img src="<?= e($loggedInUser['avatar_path']) ?>" alt=""><?php else: ?><?= e(mb_substr($loggedInUser['name'], 0, 1)) ?><?php endif; ?></span>
                <span>Profil</span>
            </a>
            <form action="logout.php" method="post" class="nav-logout">
                <?= csrf_field() ?>
                <button class="button button-quiet button-small" type="submit">Keluar</button>
            </form>
        <?php else: ?>
            <a href="index.php#fitur">Fitur</a>
            <a class="button button-quiet button-small" href="login.php">Masuk</a>
            <a class="button button-primary button-small" href="register.php">Buat akun</a>
        <?php endif; ?>
    </nav>
</header>
<main>
    <?php if ($flashMessage): ?>
        <div class="flash flash-<?= e($flashMessage['type']) ?>" role="status" data-flash>
            <span><?= e($flashMessage['message']) ?></span>
            <button type="button" aria-label="Tutup notifikasi" data-dismiss-flash>&times;</button>
        </div>
    <?php endif; ?>
