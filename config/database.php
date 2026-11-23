
<?php

$host = getenv('DB_HOST') ?: '127.0.0.1';
$port = getenv('DB_PORT') ?: '3306';
$name = getenv('DB_NAME') ?: 'main';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: '';
$sslSetting = getenv('DB_SSL');
$ssl = $sslSetting === false
    ? !in_array(strtolower($host), ['127.0.0.1', 'localhost', '::1'], true)
    : filter_var($sslSetting, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
$sslVerifySetting = getenv('DB_SSL_VERIFY');
$sslVerify = $sslVerifySetting === false
    ? true
    : filter_var($sslVerifySetting, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
$sslCa = getenv('DB_SSL_CA') ?: '';

if (!is_bool($ssl) || !is_bool($sslVerify)) {
    throw new InvalidArgumentException('DB_SSL and DB_SSL_VERIFY must be set to true or false.');
}

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

if ($ssl === true) {
    if ($sslCa !== '') {
        $options[PDO::MYSQL_ATTR_SSL_CA] = $sslCa;
    } elseif (is_file('/etc/ssl/certs/ca-certificates.crt')) {
        $options[PDO::MYSQL_ATTR_SSL_CA] = '/etc/ssl/certs/ca-certificates.crt';
    }
    $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = $sslVerify;
}

return new PDO(
    "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4",
    $user,
    $pass,
    $options
);
