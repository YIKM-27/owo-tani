<?php

function fetch_location_json(string $url, string $userAgent): array
{
    if (!function_exists('curl_init')) {
        throw new RuntimeException('Ekstensi cURL PHP belum aktif.');
    }

    $handle = curl_init($url);
    curl_setopt_array($handle, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 4,
        CURLOPT_TIMEOUT => 9,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 2,
        CURLOPT_MAXFILESIZE => 5242880,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_USERAGENT => $userAgent,
        CURLOPT_HTTPHEADER => ['Accept: application/json'],
    ]);
    $response = curl_exec($handle);
    $status = (int) curl_getinfo($handle, CURLINFO_HTTP_CODE);
    $error = curl_error($handle);
    curl_close($handle);

    if ($response === false || $status < 200 || $status >= 300) {
        $detail = $error !== '' ? $error : 'HTTP ' . $status;
        throw new RuntimeException('Layanan lokasi tidak dapat diakses: ' . $detail);
    }

    $data = json_decode($response, true);
    if (!is_array($data)) {
        throw new RuntimeException('Respons layanan lokasi tidak dapat dibaca.');
    }
    return $data;
}

function location_name_key(string $name, bool $simplify = false): string
{
    $name = mb_strtolower(trim($name));
    if ($simplify) {
        if (str_contains($name, 'jakarta')) {
            return 'jakarta';
        }
        if (str_contains($name, 'yogyakarta')) {
            return 'yogyakarta';
        }
        if (str_contains($name, 'aceh')) {
            return 'aceh';
        }
        $name = preg_replace('/^(kabupaten administratif|kabupaten|kab\\.?|kota administrasi|kota|provinsi|propinsi|dki|daerah khusus ibukota|daerah istimewa|di)\\s+/u', '', $name);
    }
    $name = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $name);
    return trim(preg_replace('/\s+/u', ' ', $name));
}

function match_location_region(array $regions, array $candidateNames): ?array
{
    foreach ($candidateNames as $candidate) {
        if (!is_string($candidate) || trim($candidate) === '') {
            continue;
        }
        $key = location_name_key($candidate);
        foreach ($regions as $region) {
            if (isset($region['name'], $region['code']) && location_name_key((string) $region['name']) === $key) {
                return $region;
            }
        }
    }

    foreach ($candidateNames as $candidate) {
        if (!is_string($candidate) || trim($candidate) === '') {
            continue;
        }
        $key = location_name_key($candidate, true);
        $matches = array_values(array_filter($regions, static fn (array $region): bool =>
            isset($region['name'], $region['code'])
            && location_name_key((string) $region['name'], true) === $key
        ));
        if (count($matches) === 1) {
            return $matches[0];
        }
    }
    return null;
}

function lookup_bmkg_location(float $latitude, float $longitude): array
{
    $reverseUrl = 'https://nominatim.openstreetmap.org/reverse?' . http_build_query([
        'format' => 'jsonv2',
        'lat' => $latitude,
        'lon' => $longitude,
        'zoom' => 18,
        'addressdetails' => 1,
    ]);
    $reverse = fetch_location_json($reverseUrl, 'OwOTani/1.0 (coordinate-to-BMKG weather lookup)');
    $address = $reverse['address'] ?? null;
    $fullAddress = trim((string) ($reverse['display_name'] ?? ''));
    if (!is_array($address) || $fullAddress === '') {
        throw new RuntimeException('Alamat untuk koordinat ini tidak ditemukan.');
    }

    $api = 'https://wilayah.id/api/';
    $provinces = fetch_location_json($api . 'provinces.json', 'OwOTani/1.0 (Indonesian administrative code lookup)')['data'] ?? [];
    $province = match_location_region($provinces, [$address['state'] ?? '', $address['province'] ?? '']);
    if (!$province) {
        return ['address' => $fullAddress, 'adm4' => null, 'error' => 'Provinsi dari koordinat belum cocok dengan daftar kode wilayah.'];
    }

    $regencies = fetch_location_json($api . 'regencies/' . rawurlencode((string) $province['code']) . '.json', 'OwOTani/1.0 (Indonesian administrative code lookup)')['data'] ?? [];
    $regency = match_location_region($regencies, [
        $address['city'] ?? '',
        $address['county'] ?? '',
        $address['municipality'] ?? '',
    ]);
    if (!$regency) {
        return ['address' => $fullAddress, 'adm4' => null, 'error' => 'Kabupaten/kota dari koordinat belum cocok dengan daftar kode wilayah.'];
    }

    $districts = fetch_location_json($api . 'districts/' . rawurlencode((string) $regency['code']) . '.json', 'OwOTani/1.0 (Indonesian administrative code lookup)')['data'] ?? [];
    $district = match_location_region($districts, [
        $address['city_district'] ?? '',
        $address['district'] ?? '',
        $address['borough'] ?? '',
    ]);
    if (!$district) {
        $villageNames = [
            $address['village'] ?? '',
            $address['town'] ?? '',
            $address['hamlet'] ?? '',
            $address['suburb'] ?? '',
            $address['neighbourhood'] ?? '',
            $address['quarter'] ?? '',
            $address['locality'] ?? '',
        ];
        $villageMatches = [];
        foreach ($districts as $districtCandidate) {
            if (empty($districtCandidate['code'])) {
                continue;
            }
            $districtVillages = fetch_location_json(
                $api . 'villages/' . rawurlencode((string) $districtCandidate['code']) . '.json',
                'OwOTani/1.0 (Indonesian administrative code lookup)'
            )['data'] ?? [];
            $candidate = match_location_region($districtVillages, $villageNames);
            if ($candidate) {
                $villageMatches[] = $candidate;
            }
        }
        if (count($villageMatches) === 1) {
            return ['address' => $fullAddress, 'adm4' => (string) $villageMatches[0]['code'], 'error' => null];
        }
        $message = count($villageMatches) > 1
            ? 'Nama desa/kelurahan ditemukan di beberapa kecamatan; kode BMKG tidak dipilih agar tidak salah lokasi.'
            : 'Kecamatan atau desa/kelurahan dari koordinat belum cocok dengan kode ADM4 BMKG.';
        return ['address' => $fullAddress, 'adm4' => null, 'error' => $message];
    }

    $villages = fetch_location_json($api . 'villages/' . rawurlencode((string) $district['code']) . '.json', 'OwOTani/1.0 (Indonesian administrative code lookup)')['data'] ?? [];
    $village = match_location_region($villages, [
        $address['village'] ?? '',
        $address['town'] ?? '',
        $address['hamlet'] ?? '',
        $address['suburb'] ?? '',
        $address['neighbourhood'] ?? '',
        $address['quarter'] ?? '',
        $address['locality'] ?? '',
    ]);
    if (!$village) {
        return ['address' => $fullAddress, 'adm4' => null, 'error' => 'Desa/kelurahan dari koordinat belum cocok dengan kode ADM4 BMKG.'];
    }

    return ['address' => $fullAddress, 'adm4' => (string) $village['code'], 'error' => null];
}

function fetch_bmkg_forecast(string $adm4): array
{
    if (!preg_match('/\A\d{2}\.\d{2}\.\d{2}\.\d{4}\z/', $adm4)) {
        throw new RuntimeException('Kode ADM4 BMKG tidak valid.');
    }
    $url = 'https://api.bmkg.go.id/publik/prakiraan-cuaca?' . http_build_query(['adm4' => $adm4]);
    $payload = fetch_location_json($url, 'OwOTani/1.0 (BMKG forecast display)');
    if (empty($payload['lokasi']) || empty($payload['data'][0]['cuaca']) || !is_array($payload['data'][0]['cuaca'])) {
        throw new RuntimeException('Data prakiraan BMKG untuk wilayah tersebut belum tersedia.');
    }
    return $payload;
}
