<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_capability('prices');

function price_illustrations(): array
{
    return [
        ['symbol' => 'GKP', 'name' => 'Gabah kering panen', 'short_name' => 'Gabah', 'unit' => 'per kg', 'price' => 7200, 'change' => 2.4, 'history_seed' => 12],
        ['symbol' => 'GKG', 'name' => 'Gabah kering giling', 'short_name' => 'Gabah giling', 'unit' => 'per kg', 'price' => 8200, 'change' => 1.1, 'history_seed' => 18],
        ['symbol' => 'JAG', 'name' => 'Jagung pipilan kering', 'short_name' => 'Jagung', 'unit' => 'per kg', 'price' => 5800, 'change' => 1.2, 'history_seed' => 24],
        ['symbol' => 'KDL', 'name' => 'Kedelai lokal', 'short_name' => 'Kedelai', 'unit' => 'per kg', 'price' => 11800, 'change' => 0.6, 'history_seed' => 31],
        ['symbol' => 'CAB', 'name' => 'Cabai merah keriting', 'short_name' => 'Cabai merah', 'unit' => 'per kg', 'price' => 38500, 'change' => 4.1, 'history_seed' => 46],
        ['symbol' => 'CRT', 'name' => 'Cabai rawit merah', 'short_name' => 'Cabai rawit', 'unit' => 'per kg', 'price' => 42500, 'change' => -1.3, 'history_seed' => 38],
        ['symbol' => 'BWM', 'name' => 'Bawang merah', 'short_name' => 'Bawang merah', 'unit' => 'per kg', 'price' => 32000, 'change' => 1.8, 'history_seed' => 43],
        ['symbol' => 'BWP', 'name' => 'Bawang putih', 'short_name' => 'Bawang putih', 'unit' => 'per kg', 'price' => 39000, 'change' => 0.4, 'history_seed' => 49],
        ['symbol' => 'KNT', 'name' => 'Kentang konsumsi', 'short_name' => 'Kentang', 'unit' => 'per kg', 'price' => 14500, 'change' => -0.8, 'history_seed' => 35],
        ['symbol' => 'TMT', 'name' => 'Tomat sayur', 'short_name' => 'Tomat', 'unit' => 'per kg', 'price' => 12500, 'change' => 3.2, 'history_seed' => 52],
        ['symbol' => 'KOL', 'name' => 'Kubis / kol', 'short_name' => 'Kubis', 'unit' => 'per kg', 'price' => 8500, 'change' => -0.4, 'history_seed' => 61],
        ['symbol' => 'WRT', 'name' => 'Wortel', 'short_name' => 'Wortel', 'unit' => 'per kg', 'price' => 11000, 'change' => 0.9, 'history_seed' => 67],
        ['symbol' => 'UBK', 'name' => 'Ubi kayu / singkong', 'short_name' => 'Singkong', 'unit' => 'per kg', 'price' => 3200, 'change' => 0.7, 'history_seed' => 72],
        ['symbol' => 'PIS', 'name' => 'Pisang kepok', 'short_name' => 'Pisang', 'unit' => 'per sisir', 'price' => 18000, 'change' => 1.5, 'history_seed' => 78],
        ['symbol' => 'KPI', 'name' => 'Kopi robusta biji', 'short_name' => 'Kopi robusta', 'unit' => 'per kg', 'price' => 72000, 'change' => 2.1, 'history_seed' => 83],
        ['symbol' => 'KKA', 'name' => 'Kakao biji kering', 'short_name' => 'Kakao', 'unit' => 'per kg', 'price' => 65000, 'change' => 1.7, 'history_seed' => 91],
    ];
}

$commodities = price_illustrations();
$pageTitle = 'Harga Pasar';
$activePage = 'prices';
require __DIR__ . '/includes/header.php';
?>
<section class="page-wrap prices-page trader-page">
    <div class="page-intro page-intro-split trader-page-intro">
        <div><div class="section-kicker">Pantau komoditas pertanian</div><h1>Harga Pasar<span class="greeting-dot">.</span></h1><p>Grafik bergaya trading untuk membaca pergerakan dan membandingkan komoditas.</p></div>
        <a class="market-source-link" href="https://panelharga.badanpangan.go.id/" target="_blank" rel="noopener noreferrer">Buka Panel Harga Pangan Bapanas <span aria-hidden="true">↗</span></a>
    </div>

    <section class="trader-terminal" aria-label="Grafik harga komoditas">
        <div class="terminal-topbar">
            <div class="terminal-brand"><span class="terminal-mark">OT</span><span>OWO TANI <small>COMMODITY WATCH</small></span></div>
            <div class="terminal-status"><span class="demo-indicator"></span><span>MODE DEMO</span><span class="terminal-separator">/</span><span>BUKAN KUTIPAN HARGA LANGSUNG</span></div>
        </div>
        <div class="terminal-layout">
            <aside class="market-watch">
                <div class="market-watch-heading"><span>DAFTAR KOMODITAS</span><span>IDR</span></div>
                <?php foreach ($commodities as $index => $item): ?>
                    <button class="watch-item <?= $index === 0 ? 'is-selected' : '' ?>" type="button" data-watch-item data-index="<?= $index ?>">
                        <span class="watch-item-symbol"><?= e($item['symbol']) ?></span>
                        <span class="watch-item-info"><strong><?= e($item['short_name']) ?></strong><small><?= e($item['unit']) ?></small></span>
                        <span class="watch-item-price"><?= format_rupiah($item['price']) ?><small class="<?= $item['change'] >= 0 ? 'quote-up' : 'quote-down' ?>"><?= $item['change'] >= 0 ? '+' : '' ?><?= e(number_format($item['change'], 1, ',', '.')) ?>%</small></span>
                    </button>
                <?php endforeach; ?>
                <div class="watch-footnote">Harga contoh ditetapkan untuk demonstrasi UI.</div>
            </aside>
            <div class="terminal-main">
                <div class="quote-heading">
                    <div class="quote-identity"><span class="section-kicker section-kicker-light" data-quote-symbol>GKP · HASIL PANEN</span><h2 data-quote-name>Gabah kering panen</h2><p data-quote-unit>Harga ilustrasi · per kg</p></div>
                    <div class="quote-numbers"><strong data-quote-price>Rp7.200</strong><span class="quote-change quote-up" data-quote-change>+2,4% <small>ilustrasi</small></span></div>
                </div>
                <div class="chart-toolbar">
                    <span>PERGERAKAN HARGA</span>
                    <div class="chart-ranges" role="group" aria-label="Rentang waktu grafik">
                        <button type="button" class="is-selected" data-chart-range="1H">1J</button>
                        <button type="button" data-chart-range="1D">1H</button>
                        <button type="button" data-chart-range="1W">1M</button>
                        <button type="button" data-chart-range="1M">1B</button>
                    </div>
                </div>
                <div class="chart-stage">
                    <div class="chart-tooltip" data-chart-tooltip hidden></div>
                    <svg class="trader-chart" data-trader-chart viewBox="0 0 1000 390" role="img" aria-label="Grafik ilustrasi perubahan harga komoditas">
                        <defs><linearGradient id="chartFill" x1="0" x2="0" y1="0" y2="1"><stop offset="0%" stop-color="#98bd8d" stop-opacity=".3"/><stop offset="100%" stop-color="#98bd8d" stop-opacity="0"/></linearGradient></defs>
                        <g data-chart-grid></g><path class="chart-area" data-chart-area></path><path class="chart-line" data-chart-line></path><g data-chart-labels></g><circle class="chart-current-point" data-chart-point r="5"></circle><rect class="chart-hit-area" data-chart-hit-area x="0" y="0" width="1000" height="340"></rect><line class="chart-crosshair" data-chart-crosshair y1="20" y2="340"></line><circle class="chart-hover-point" data-chart-hover-point r="5"></circle>
                    </svg>
                </div>
                <div class="chart-legend"><span>PERIODE SIMULASI</span><span data-chart-period>Intrahari · data ilustrasi</span><span>SUMBER HARGA <strong>Belum terhubung</strong></span></div>
            </div>
        </div>
        <div class="terminal-bottom"><span>OwO Tani · Commodity Watch</span><span>Angka pada halaman ini adalah data dummy dan tidak diperbarui dari pasar.</span></div>
    </section>

    <section class="market-summary-section">
        <div class="market-summary-heading"><div><div class="section-kicker">Ringkasan komoditas</div><h2>Contoh harga untuk demo.</h2></div><span class="summary-disclaimer">Semua nilai ilustrasi</span></div>
        <div class="market-summary-table">
            <div class="summary-table-head"><span>Komoditas</span><span>Simbol</span><span>Harga contoh</span><span>Perubahan contoh</span><span>Catatan</span></div>
            <?php foreach ($commodities as $index => $item): ?>
                <button class="summary-row" type="button" data-watch-item data-index="<?= $index ?>">
                    <span class="summary-name"><?= e($item['name']) ?><small>Hasil pertanian · Indonesia</small></span>
                    <span class="summary-symbol"><?= e($item['symbol']) ?></span>
                    <strong><?= format_rupiah($item['price']) ?><small>/ kg</small></strong>
                    <span class="summary-change <?= $item['change'] >= 0 ? 'quote-up' : 'quote-down' ?>"><?= $item['change'] >= 0 ? '+' : '' ?><?= e(number_format($item['change'], 1, ',', '.')) ?>%</span>
                    <span class="summary-note">Contoh UI</span>
                </button>
            <?php endforeach; ?>
        </div>
    </section>
    <div class="market-data-notice"><span class="note-mark">i</span><p><strong>Belum ada umpan harga langsung di aplikasi.</strong> Data harga pertanian yang bisa dipastikan dari sumber publik umumnya dilaporkan berkala dan tidak berupa tick real-time seperti saham. Panel Harga Pangan Bapanas dapat dibuka untuk melihat informasi resminya. Grafik di atas hanya demonstrasi tampilan; jangan gunakan nilainya untuk transaksi atau keputusan panen.</p></div>
</section>
<script type="application/json" id="commodity-data"><?= json_encode($commodities, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
<?php require __DIR__ . '/includes/footer.php'; ?>
