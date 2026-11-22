<?php
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/location.php';
require_capability('land');

function weather_adaptation_suggestions(array $current, array $forecast): array
{
    $suggestions = [];
    $weatherText = mb_strtolower((string) ($current['description'] ?? ''));
    $currentPrecipitation = $current['precipitation'] ?? null;
    $currentRainChance = $current['rain_chance'] ?? null;
    $hasHeavyRain = (is_numeric($currentPrecipitation) && (float) $currentPrecipitation >= 10)
        || (is_numeric($currentRainChance) && (float) $currentRainChance >= 70);
    $dailyForecast = [];

    foreach ($forecast as $period) {
        $description = mb_strtolower((string) ($period['description'] ?? ''));
        $weatherText .= ' ' . $description;
        $precipitation = $period['precipitation'] ?? null;
        $rainChance = $period['rain_chance'] ?? null;
        if ((is_numeric($precipitation) && (float) $precipitation >= 10)
            || (is_numeric($rainChance) && (float) $rainChance >= 70)
            || str_contains($description, 'hujan lebat')) {
            $hasHeavyRain = true;
        }

        $date = substr((string) ($period['time'] ?? ''), 0, 10);
        if ($date !== '' && is_numeric($period['temperature'] ?? null)) {
            $dailyForecast[$date][] = $period;
        }
    }
    $hasHeavyRain = $hasHeavyRain || str_contains($weatherText, 'hujan lebat');

    if (str_contains($weatherText, 'badai petir') || str_contains($weatherText, 'petir')) {
        $suggestions[] = 'Ada potensi badai petir. Amankan alat dan hindari bekerja di lahan terbuka saat terjadi petir.';
    }
    if ($hasHeavyRain) {
        $suggestions[] = 'Prakiraan menunjukkan hujan lebat atau peluang hujan tinggi. Tunda pemupukan dan periksa saluran drainase lahan.';
    }

    $hotDryDates = [];
    foreach ($dailyForecast as $date => $periods) {
        $hasHotPeriod = false;
        $hasPrecipitationData = false;
        $isDry = true;
        foreach ($periods as $period) {
            $hasHotPeriod = $hasHotPeriod || (float) $period['temperature'] >= 30;
            $precipitation = $period['precipitation'] ?? null;
            $rainChance = $period['rain_chance'] ?? null;
            if (is_numeric($precipitation)) {
                $hasPrecipitationData = true;
                $isDry = $isDry && (float) $precipitation < 0.1;
            }
            if (is_numeric($rainChance)) {
                $hasPrecipitationData = true;
                $isDry = $isDry && (float) $rainChance <= 20;
            }
        }
        if ($hasHotPeriod && $hasPrecipitationData && $isDry) {
            $hotDryDates[] = $date;
        }
    }
    sort($hotDryDates);
    for ($index = 1; $index < count($hotDryDates); $index++) {
        if (date('Y-m-d', strtotime($hotDryDates[$index - 1] . ' +1 day')) === $hotDryDates[$index]) {
            $suggestions[] = 'Prakiraan menunjukkan beberapa hari panas dan kering berturut-turut. Jadwalkan penyiraman pagi atau sore dan pertimbangkan penggunaan mulsa.';
            break;
        }
    }

    if (!$suggestions && is_numeric($current['humidity'] ?? null) && (float) $current['humidity'] <= 35
        && is_numeric($current['temperature'] ?? null) && (float) $current['temperature'] >= 30) {
        $suggestions[] = 'Suhu tinggi dan kelembapan rendah. Periksa kebutuhan air tanaman dan utamakan penyiraman pagi atau sore.';
    }
    if (!$suggestions) {
        $suggestions[] = 'Kondisi cuaca saat ini tampak cukup mendukung perawatan rutin. Tetap sesuaikan kegiatan dengan kondisi nyata di lahan.';
    }

    return $suggestions;
}

$stmt = db()->prepare('SELECT * FROM farms WHERE id = ? AND user_id = ?');
$stmt->execute([filter_input(INPUT_GET, 'farm_id', FILTER_VALIDATE_INT), current_user()['id']]);
$farm = $stmt->fetch();
if (!$farm) {
    set_flash('error', 'Lahan tidak ditemukan.');
    redirect('land.php');
}

$locationAddress = (string) ($farm['location_address'] ?? '');
$adm4Code = (string) ($farm['bmkg_adm4'] ?? '');
$bmkgError = '';
$bmkgPayload = null;
$current = null;
$hourlyForecast = [];
$adviceForecast = [];
$sourceLabel = '';
$openMeteoPayload = null;

if ($locationAddress === '' || $adm4Code === '') {
    try {
        $resolvedLocation = lookup_bmkg_location((float) $farm['latitude'], (float) $farm['longitude']);
    } catch (RuntimeException $exception) {
        $bmkgError = $exception->getMessage();
    }
    if (isset($resolvedLocation)) {
        $locationAddress = (string) ($resolvedLocation['address'] ?? '');
        $adm4Code = (string) ($resolvedLocation['adm4'] ?? '');
        $bmkgError = (string) ($resolvedLocation['error'] ?? '');
        if ($locationAddress !== '') {
            db()->prepare('UPDATE farms SET location_address = ?, bmkg_adm4 = ? WHERE id = ? AND user_id = ?')
                ->execute([$locationAddress, $adm4Code ?: null, $farm['id'], current_user()['id']]);
        }
    }
}

if ($adm4Code !== '') {
    try {
        $bmkgPayload = fetch_bmkg_forecast($adm4Code);
        $forecastEntries = [];
        foreach ($bmkgPayload['data'][0]['cuaca'] as $periods) {
            if (is_array($periods)) {
                foreach ($periods as $period) {
                    if (is_array($period) && !empty($period['local_datetime'])) {
                        $forecastEntries[] = $period;
                    }
                }
            }
        }
        if (!$forecastEntries) {
            throw new RuntimeException('BMKG belum menyediakan rincian prakiraan untuk kode wilayah ini.');
        }

        $timezoneName = (string) ($bmkgPayload['lokasi']['timezone'] ?? 'Asia/Jakarta');
        try {
            $timezone = new DateTimeZone($timezoneName);
        } catch (Exception) {
            $timezone = new DateTimeZone('Asia/Jakarta');
        }
        $now = new DateTimeImmutable('now', $timezone);
        $currentIndex = 0;
        foreach ($forecastEntries as $index => $period) {
            try {
                if (new DateTimeImmutable((string) $period['local_datetime'], $timezone) >= $now) {
                    $currentIndex = $index;
                    break;
                }
            } catch (Exception) {
                continue;
            }
        }

        $nearestForecast = $forecastEntries[$currentIndex];
        $current = [
            'description' => $nearestForecast['weather_desc'] ?? 'Kondisi cuaca',
            'temperature' => $nearestForecast['t'] ?? '—',
            'humidity' => $nearestForecast['hu'] ?? '—',
            'precipitation' => $nearestForecast['tp'] ?? '—',
            'wind_speed' => $nearestForecast['ws'] ?? '—',
            'time' => $nearestForecast['local_datetime'],
        ];
        foreach (array_slice($forecastEntries, $currentIndex) as $period) {
            $adviceForecast[] = [
                'time' => $period['local_datetime'] ?? '',
                'temperature' => $period['t'] ?? null,
                'description' => $period['weather_desc'] ?? '',
                'humidity' => $period['hu'] ?? null,
                'precipitation' => $period['tp'] ?? null,
            ];
        }
        foreach (array_slice($forecastEntries, $currentIndex, 5) as $period) {
            $hourlyForecast[] = [
                'time' => $period['local_datetime'],
                'temperature' => $period['t'] ?? null,
                'description' => $period['weather_desc'] ?? 'Kondisi cuaca',
                'humidity' => $period['hu'] ?? null,
                'precipitation' => $period['tp'] ?? null,
            ];
        }
        $sourceLabel = 'BMKG · kode ADM4 ' . $adm4Code;
    } catch (RuntimeException $exception) {
        $bmkgError = $exception->getMessage();
    }
}

if ($current === null) {
    if (!function_exists('curl_init')) {
        $openMeteoError = 'Ekstensi cURL PHP belum aktif.';
    } else {
        $query = http_build_query([
            'latitude' => $farm['latitude'],
            'longitude' => $farm['longitude'],
            'current' => 'temperature_2m,relative_humidity_2m,apparent_temperature,precipitation,weather_code,wind_speed_10m',
            'hourly' => 'temperature_2m,precipitation_probability,precipitation',
            'forecast_days' => 2,
            'timezone' => 'auto',
        ]);
        $handle = curl_init('https://api.open-meteo.com/v1/forecast?' . $query);
        curl_setopt_array($handle, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 12,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
        ]);
        $response = curl_exec($handle);
        $httpStatus = (int) curl_getinfo($handle, CURLINFO_HTTP_CODE);
        $curlError = curl_error($handle);
        curl_close($handle);
        if ($response === false || $httpStatus < 200 || $httpStatus >= 300) {
            $openMeteoError = $curlError
                ? 'Tidak dapat mengambil prakiraan cadangan: ' . $curlError
                : 'Layanan prakiraan cadangan tidak tersedia (HTTP ' . $httpStatus . ').';
        } else {
            $openMeteoPayload = json_decode($response, true);
            if (!is_array($openMeteoPayload) || !isset($openMeteoPayload['current'], $openMeteoPayload['hourly']['time'])) {
                $openMeteoError = 'Respons prakiraan cadangan tidak dapat dibaca.';
            } else {
                $weatherDescriptions = [
                    0 => 'Cerah', 1 => 'Cerah berawan', 2 => 'Berawan sebagian', 3 => 'Berawan',
                    45 => 'Berkabut', 48 => 'Kabut tebal', 51 => 'Gerimis ringan', 53 => 'Gerimis',
                    55 => 'Gerimis lebat', 61 => 'Hujan ringan', 63 => 'Hujan', 65 => 'Hujan lebat',
                    71 => 'Salju ringan', 73 => 'Salju', 75 => 'Salju lebat', 80 => 'Hujan lokal',
                    81 => 'Hujan lokal', 82 => 'Hujan lokal lebat', 95 => 'Badai petir',
                    96 => 'Badai petir dan hujan es', 99 => 'Badai petir dan hujan es lebat',
                ];
                $openCurrent = $openMeteoPayload['current'];
                $weatherCode = (int) ($openCurrent['weather_code'] ?? -1);
                $current = [
                    'description' => $weatherDescriptions[$weatherCode] ?? 'Kondisi cuaca',
                    'temperature' => $openCurrent['temperature_2m'] ?? '—',
                    'humidity' => $openCurrent['relative_humidity_2m'] ?? '—',
                    'precipitation' => $openCurrent['precipitation'] ?? '—',
                    'wind_speed' => $openCurrent['wind_speed_10m'] ?? '—',
                    'apparent_temperature' => $openCurrent['apparent_temperature'] ?? '—',
                    'time' => $openCurrent['time'] ?? '',
                ];
                $currentHour = substr((string) ($openCurrent['time'] ?? ''), 0, 13);
                foreach ($openMeteoPayload['hourly']['time'] as $index => $time) {
                    if (substr((string) $time, 0, 13) < $currentHour) {
                        continue;
                    }
                    $hourlyForecast[] = [
                        'time' => $time,
                        'temperature' => $openMeteoPayload['hourly']['temperature_2m'][$index] ?? null,
                        'rain_chance' => $openMeteoPayload['hourly']['precipitation_probability'][$index] ?? null,
                        'precipitation' => $openMeteoPayload['hourly']['precipitation'][$index] ?? null,
                    ];
                }
                $adviceForecast = $hourlyForecast;
                $hourlyForecast = array_slice($hourlyForecast, 0, 12);
                $sourceLabel = 'Open-Meteo · prakiraan koordinat (cadangan)';
            }
        }
    }
    if ($current === null) {
        $apiError = trim($bmkgError . ' ' . ($openMeteoError ?? ''));
    }
}

$pageTitle = 'Prakiraan cuaca';
$activePage = 'land';
require __DIR__ . '/includes/header.php';
?>
<section class="page-wrap weather-page">
    <a class="back-link" href="land.php">← Kembali ke Lahanku</a>
    <div class="page-intro">
        <div class="section-kicker">Cuaca di titik lahanmu</div>
        <h1><?= e($farm['name']) ?><span class="greeting-dot">.</span></h1>
        <p><?= e($farm['crop']) ?> · <?= e(number_format((float) $farm['latitude'], 5)) ?>, <?= e(number_format((float) $farm['longitude'], 5)) ?></p>
        <?php if ($locationAddress !== ''): ?><div class="weather-location"><strong>Alamat terdeteksi</strong><p><?= e($locationAddress) ?></p></div><?php endif; ?>
        <?php if ($adm4Code !== ''): ?><p class="weather-code"><strong>Kode BMKG (ADM4):</strong> <?= e($adm4Code) ?></p><?php endif; ?>
        <a class="button button-outline map-link" target="_blank" rel="noopener noreferrer" href="https://www.google.com/maps?q=<?= rawurlencode($farm['latitude'] . ',' . $farm['longitude']) ?>">Buka titik di peta <span aria-hidden="true">↗</span></a>
    </div>
    <?php if ($current === null): ?>
        <div class="weather-error"><span class="note-mark">!</span><div><strong>Prakiraan belum dapat ditampilkan.</strong><p><?= e($apiError ?? 'Layanan cuaca belum tersedia.') ?></p></div></div>
    <?php else: ?>
        <?php if ($sourceLabel !== '' && !str_starts_with($sourceLabel, 'BMKG') && $bmkgError !== ''): ?>
            <div class="weather-source-notice"><strong>BMKG belum dapat digunakan.</strong> <?= e($bmkgError) ?> Saat ini ditampilkan prakiraan cadangan dari Open-Meteo.</div>
        <?php endif; ?>
        <?php $weatherSuggestions = weather_adaptation_suggestions($current, $adviceForecast); ?>
        <aside class="weather-advice" aria-labelledby="weather-advice-title">
            <div class="section-kicker">SDG 13 · adaptasi iklim</div>
            <h2 id="weather-advice-title">Saran untuk lahanmu</h2>
            <ul><?php foreach ($weatherSuggestions as $suggestion): ?><li><?= e($suggestion) ?></li><?php endforeach; ?></ul>
            <p>Saran ini bersifat umum berdasarkan prakiraan, bukan pengganti arahan penyuluh pertanian.</p>
        </aside>
        <section class="weather-current">
            <div><span class="section-kicker section-kicker-light">PRAKIRAAN TERDEKAT · <?= e($current['time'] ?? '') ?></span><h2><?= e($current['description'] ?? 'Kondisi cuaca') ?></h2><p>Sumber: <?= e($sourceLabel) ?></p></div>
            <strong><?= e($current['temperature'] ?? '—') ?><span>°C</span></strong>
        </section>
        <div class="weather-metrics">
            <div><span>Terasa seperti</span><strong><?= e($current['apparent_temperature'] ?? $current['temperature'] ?? '—') ?> °C</strong></div>
            <div><span>Kelembapan</span><strong><?= e($current['humidity'] ?? '—') ?>%</strong></div>
            <div><span>Curah hujan</span><strong><?= e($current['precipitation'] ?? '—') ?> mm</strong></div>
            <div><span>Kecepatan angin</span><strong><?= e($current['wind_speed'] ?? '—') ?> km/j</strong></div>
        </div>
        <div class="weather-forecast-heading"><div class="section-kicker">Prakiraan per jam</div><h2>Rencanakan aktivitas lahan.</h2><p><?= str_starts_with($sourceLabel, 'BMKG') ? 'Prakiraan beberapa jam mendatang dari BMKG.' : 'Perkiraan 12 jam berikutnya, waktu lokal.' ?></p></div>
        <?php if ($hourlyForecast): ?>
            <div class="weather-grid"><?php foreach ($hourlyForecast as $entry): ?>
                <article class="weather-card">
                    <span class="weather-time"><?= e(substr((string) $entry['time'], 11, 5)) ?></span>
                    <strong class="weather-temperature"><?= e($entry['temperature'] ?? '—') ?>°</strong>
                    <span class="weather-description"><?= e($entry['description'] ?? ('Hujan ' . ($entry['rain_chance'] ?? '—') . '%')) ?></span>
                    <small><?= isset($entry['humidity']) ? 'Kelembapan ' . e($entry['humidity']) . '%' : 'Curah hujan ' . e($entry['precipitation'] ?? '—') . ' mm' ?></small>
                </article>
            <?php endforeach; ?></div>
        <?php else: ?>
            <div class="weather-error"><div><strong>Prakiraan per jam belum tersedia.</strong></div></div>
        <?php endif; ?>
        <p class="data-note">Cuaca BMKG menggunakan kode ADM4 yang dicocokkan otomatis dari alamat koordinat. Prakiraan dapat mewakili wilayah desa/kelurahan, bukan sensor persis di petak lahan. Alamat dari <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener noreferrer">OpenStreetMap</a>; kode wilayah dari <a href="https://wilayah.id/" target="_blank" rel="noopener noreferrer">wilayah.id</a>. Jika BMKG tidak tersedia, halaman memakai prakiraan koordinat dari <a href="https://open-meteo.com/" target="_blank" rel="noopener noreferrer">Open-Meteo</a>.</p>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
