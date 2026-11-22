<?php

declare(strict_types=1);

$isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
session_set_cookie_params([
    'httponly' => true,
    'secure' => $isHttps,
    'samesite' => 'Lax',
]);
session_start();

function db(): PDO
{
    static $connection;
    if (!$connection instanceof PDO) {
        $connection = require __DIR__ . '/../config/database.php';
    }
    return $connection;
}

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $location): never
{
    header('Location: ' . $location);
    exit;
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function require_login(): void
{
    if (current_user() === null) {
        set_flash('error', 'Silakan masuk terlebih dahulu untuk melanjutkan.');
        redirect('login.php');
    }
}

function can_access(string $feature): bool
{
    $role = current_user()['role'] ?? null;
    if ($role === 'admin') {
        return true;
    }
    $permissions = [
        'land' => ['petani', 'petani_penjual'],
        'marketplace' => ['petani', 'penjual', 'petani_penjual', 'warga'],
        'prices' => ['petani', 'penjual', 'petani_penjual', 'warga'],
        'sell' => ['penjual', 'petani_penjual'],
    ];
    return in_array($role, $permissions[$feature] ?? [], true);
}

function require_capability(string $feature): void
{
    require_login();
    if (!can_access($feature)) {
        set_flash('error', 'Fitur ini belum tersedia untuk peran akun Anda.');
        redirect('dashboard.php');
    }
}

function set_flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function take_flash(): ?array
{
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $submitted = $_POST['csrf_token'] ?? '';
    if (!is_string($submitted) || !hash_equals(csrf_token(), $submitted)) {
        http_response_code(419);
        exit('Sesi formulir tidak valid. Muat ulang halaman, lalu coba kembali.');
    }
}

function role_label(string $role): string
{
    return [
        'admin' => 'Administrator',
        'petani' => 'Petani',
        'penjual' => 'Penjual',
        'petani_penjual' => 'Petani & Penjual',
        'warga' => 'Warga / Wargi',
    ][$role] ?? 'Pengguna';
}

function format_rupiah(int|float|string $amount): string
{
    return 'Rp' . number_format((float) $amount, 0, ',', '.');
}

function product_image_url(array $product): string
{
    if (!empty($product['image_path'])) {
        return (string) $product['image_path'];
    }

    $name = mb_strtolower((string) ($product['name'] ?? ''));
    if (str_contains($name, 'cabai') || str_contains($name, 'tomat')) {
        return 'https://images.unsplash.com/photo-1592841200221-a6898f307baa?auto=format&fit=crop&w=900&q=82';
    }
    if (str_contains($name, 'kentang') || str_contains($name, 'sayur') || ($product['category'] ?? '') === 'Hasil panen') {
        return 'https://images.unsplash.com/photo-1518977676601-b53f82aba655?auto=format&fit=crop&w=900&q=82';
    }
    if (($product['category'] ?? '') === 'Alat tani') {
        return 'https://images.unsplash.com/photo-1500382017468-9049fed747ef?auto=format&fit=crop&w=900&q=82';
    }
    if (($product['category'] ?? '') === 'Pupuk') {
        return 'https://images.unsplash.com/photo-1540420773420-3366772f4999?auto=format&fit=crop&w=900&q=82';
    }
    return 'https://images.unsplash.com/photo-1500382017468-9049fed747ef?auto=format&fit=crop&w=900&q=82';
}

function save_uploaded_image(string $field, string $kind, int $maxBytes = 5242880): ?string
{
    if (!in_array($kind, ['products', 'profiles'], true)) {
        throw new InvalidArgumentException('Jenis gambar tidak dikenal.');
    }
    if (!isset($_FILES[$field]) || !is_array($_FILES[$field])) {
        return null;
    }

    $file = $_FILES[$field];
    $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($error === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE) {
        throw new RuntimeException('Ukuran gambar maksimal 5 MB.');
    }
    if ($error !== UPLOAD_ERR_OK || !isset($file['tmp_name'], $file['size'])
        || !is_string($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        throw new RuntimeException('Upload gambar gagal. Pilih gambar lain dan coba kembali.');
    }
    if ((int) $file['size'] < 1 || (int) $file['size'] > $maxBytes) {
        throw new RuntimeException('Ukuran gambar maksimal 5 MB.');
    }

    $imageInfo = @getimagesize($file['tmp_name']);
    $mime = $imageInfo['mime'] ?? '';
    if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)
        || empty($imageInfo[0]) || empty($imageInfo[1])
        || $imageInfo[0] * $imageInfo[1] > 12000000) {
        throw new RuntimeException('Gunakan gambar JPG, PNG, atau WebP dengan resolusi yang wajar.');
    }

    $source = @imagecreatefromstring((string) file_get_contents($file['tmp_name']));
    if (!$source) {
        throw new RuntimeException('File gambar tidak dapat dibaca.');
    }

    $width = imagesx($source);
    $height = imagesy($source);
    $scale = min(1, 1600 / max($width, $height));
    $outputWidth = max(1, (int) round($width * $scale));
    $outputHeight = max(1, (int) round($height * $scale));
    $image = imagecreatetruecolor($outputWidth, $outputHeight);
    if (!$image) {
        imagedestroy($source);
        throw new RuntimeException('Gambar terlalu besar untuk diproses.');
    }
    $background = imagecolorallocate($image, 255, 255, 255);
    imagefill($image, 0, 0, $background);
    imagecopyresampled($image, $source, 0, 0, 0, 0, $outputWidth, $outputHeight, $width, $height);
    imagedestroy($source);

    $directory = dirname(__DIR__) . '/uploads/' . $kind;
    if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
        imagedestroy($image);
        throw new RuntimeException('Folder penyimpanan gambar tidak dapat dibuat.');
    }

    $filename = bin2hex(random_bytes(16)) . '.jpg';
    $absolutePath = $directory . '/' . $filename;
    $saved = imagejpeg($image, $absolutePath, 86);
    imagedestroy($image);
    if (!$saved) {
        if (is_file($absolutePath) && !unlink($absolutePath)) {
            throw new RuntimeException('Gambar gagal disimpan dan file sementara tidak dapat dibersihkan.');
        }
        throw new RuntimeException('Gambar gagal disimpan. Periksa izin folder uploads.');
    }

    return 'uploads/' . $kind . '/' . $filename;
}

function delete_uploaded_image(?string $relativePath): void
{
    if (!$relativePath || !preg_match('~\Auploads/(?:products|profiles)/[a-f0-9]{32}\.jpg\z~', $relativePath)) {
        return;
    }
    $absolutePath = dirname(__DIR__) . '/' . $relativePath;
    if (is_file($absolutePath) && !unlink($absolutePath)) {
        throw new RuntimeException('File gambar lama tidak dapat dihapus.');
    }
}
