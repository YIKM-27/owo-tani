<?php

return new PDO(
    'mysql:host=' . (getenv('DB_HOST') ?: '127.0.0.1')
        . ';dbname=' . (getenv('DB_NAME') ?: 'owo_tani')
        . ';charset=utf8mb4',
    getenv('DB_USER') ?: 'root',
    getenv('DB_PASS') ?: '',
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]
);
