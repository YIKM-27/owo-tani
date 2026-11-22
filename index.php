<?php
require __DIR__ . '/includes/bootstrap.php';

if (current_user()) {
    redirect('dashboard.php');
}

$pageTitle = 'Pertanian yang tumbuh bersama';
$activePage = 'home';
require __DIR__ . '/includes/header.php';
?>
<section class="hero">
    <div class="hero-photo" aria-hidden="true"></div>
    <div class="hero-content">
        <span class="eyebrow eyebrow-light"><span class="status-dot"></span> Ekosistem pertanian Indonesia</span>
        <h1>Tumbuh lebih baik,<br><em>bersama</em> OwO Tani.</h1>
        <p>Satu ruang untuk merawat lahan, menemukan kebutuhan tani, dan membaca peluang pasar dengan lebih percaya diri.</p>
        <div class="hero-actions">
            <a class="button button-cream" href="register.php">Mulai perjalanan <span aria-hidden="true">↗</span></a>
            <a class="text-link text-link-light" href="#fitur">Jelajahi fitur <span aria-hidden="true">↓</span></a>
        </div>
        <div class="hero-note">
            <span class="hero-note-icon" aria-hidden="true">✳</span>
            <span>Dibuat untuk petani, penjual, dan siapa pun yang peduli pangan.</span>
        </div>
    </div>
    <div class="hero-caption"><span>01</span> Kebaikan tumbuh dari tanah yang dirawat.</div>
</section>

<section class="intro-section content-section">
    <div class="section-kicker">Dari lahan, untuk kehidupan</div>
    <div class="intro-grid">
        <h2>Teknologi yang dekat<br>dengan keseharian tani.</h2>
        <div>
            <p class="lead-copy">OwO Tani membantu menghubungkan hal-hal penting dalam perjalanan pertanian—mulai dari catatan lahan, kebutuhan produksi, sampai informasi harga hasil panen.</p>
            <a class="text-link" href="register.php">Kenali ruang tumbuhmu <span aria-hidden="true">↗</span></a>
        </div>
    </div>
</section>

<section class="feature-section content-section" id="fitur">
    <div class="section-heading">
        <div>
            <div class="section-kicker">Satu tempat, banyak kemungkinan</div>
            <h2>Yang kamu butuhkan<br>untuk terus bertumbuh.</h2>
        </div>
        <p>Mulai dari langkah kecil. Kelola aktivitasmu, temukan mitra, dan ambil keputusan dengan informasi yang lebih tertata.</p>
    </div>
    <div class="feature-grid">
        <article class="feature-card feature-card-land">
            <span class="feature-number">01 / LAHAN</span>
            <div class="feature-icon" aria-hidden="true">⌁</div>
            <h3>Kenali setiap petak</h3>
            <p>Simpan detail lahan, lokasi, tanaman, dan catatan penyiraman atau pemupukan.</p>
            <span class="feature-arrow" aria-hidden="true">↗</span>
        </article>
        <article class="feature-card feature-card-market">
            <span class="feature-number">02 / NIAGA</span>
            <div class="feature-icon" aria-hidden="true">↗</div>
            <h3>Niaga Tani</h3>
            <p>Temukan benih, pupuk, alat, dan hasil panen dari sesama pelaku pertanian.</p>
            <span class="feature-arrow" aria-hidden="true">↗</span>
        </article>
        <article class="feature-card feature-card-price">
            <span class="feature-number">03 / HARGA</span>
            <div class="feature-icon" aria-hidden="true">⌁</div>
            <h3>Baca arah pasar</h3>
            <p>Pantau contoh tren harga komoditas untuk membantu menyusun rencana panen.</p>
            <span class="feature-arrow" aria-hidden="true">↗</span>
        </article>
    </div>
</section>

<section class="feature-section content-section sdg-section" id="sdgs">
    <div class="section-heading">
        <div><div class="section-kicker">Dampak yang ingin didukung</div><h2>Selaras dengan SDGs.</h2></div>
        <p>Fitur OwO Tani dirancang untuk membantu praktik pertanian dan niaga yang lebih tangguh, inklusif, dan bertanggung jawab.</p>
    </div>
    <div class="feature-grid">
        <article class="feature-card feature-card-market">
            <span class="feature-number">SDG 2 / ZERO HUNGER</span>
            <div class="feature-icon" aria-hidden="true">✳</div>
            <h3>Tanpa Kelaparan</h3>
            <p>Niaga Tani membantu mempertemukan hasil panen sortiran yang tetap layak konsumsi dengan pembeli, agar pangan tidak terbuang.</p>
            <a class="feature-card-link" href="marketplace.php">Jelajahi Niaga Tani <span aria-hidden="true">↗</span></a>
        </article>
        <article class="feature-card feature-card-price">
            <span class="feature-number">SDG 9 / INDUSTRI, INOVASI & INFRASTRUKTUR</span>
            <div class="feature-icon" aria-hidden="true">↗</div>
            <h3>Industri, Inovasi, dan Infrastruktur</h3>
            <p>Catatan logistik pada pesanan dan informasi komoditas mendukung alur niaga digital yang lebih tertata. Harga yang ditampilkan masih ilustrasi.</p>
            <a class="feature-card-link" href="orders.php">Lihat pesanan <span aria-hidden="true">↗</span></a>
        </article>
        <article class="feature-card feature-card-land">
            <span class="feature-number">SDG 13 / CLIMATE ACTION</span>
            <div class="feature-icon" aria-hidden="true">⌁</div>
            <h3>Penanganan Perubahan Iklim</h3>
            <p>Prakiraan cuaca dan saran adaptasi umum membantu petani mempertimbangkan risiko cuaca saat merencanakan perawatan lahan.</p>
            <a class="feature-card-link" href="land.php">Kelola lahanku <span aria-hidden="true">↗</span></a>
        </article>
    </div>
</section>

<section class="join-section">
    <div class="join-copy">
        <div class="section-kicker section-kicker-light">Ruang tumbuhmu menanti</div>
        <h2>Mulai dari satu langkah kecil hari ini.</h2>
        <p>Buat akun, pilih peranmu, dan temukan fitur yang paling sesuai dengan perjalanan tanimu.</p>
        <a class="button button-cream" href="register.php">Gabung dengan OwO Tani <span aria-hidden="true">↗</span></a>
    </div>
    <div class="join-decoration" aria-hidden="true"><span>o</span><span>t</span></div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
