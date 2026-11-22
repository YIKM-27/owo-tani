CREATE DATABASE IF NOT EXISTS owo_tani
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE owo_tani;

CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'petani', 'penjual', 'petani_penjual', 'warga') NOT NULL DEFAULT 'warga',
    avatar_path VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS farms (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(120) NOT NULL,
    land_type ENUM('Sawah', 'Kebun', 'Ladang', 'Hidroponik', 'Lainnya') NOT NULL,
    area_ha DECIMAL(8,2) NOT NULL,
    latitude DECIMAL(10,7) NOT NULL,
    longitude DECIMAL(10,7) NOT NULL,
    location_address VARCHAR(700) NULL,
    bmkg_adm4 VARCHAR(20) NULL,
    crop VARCHAR(100) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_farms_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_farms_user (user_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS farm_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    farm_id BIGINT UNSIGNED NOT NULL,
    activity ENUM('Penyiraman', 'Pemupukan', 'Penanganan cuaca ekstrem', 'Pengomposan') NOT NULL,
    note VARCHAR(255) NULL,
    logged_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_farm_logs_farm FOREIGN KEY (farm_id) REFERENCES farms(id) ON DELETE CASCADE,
    INDEX idx_farm_logs_farm_date (farm_id, logged_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS products (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    seller_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(140) NOT NULL,
    category ENUM('Benih', 'Pupuk', 'Alat tani', 'Hasil panen', 'Hasil panen sortiran', 'Lainnya') NOT NULL,
    description TEXT NOT NULL,
    image_path VARCHAR(255) NULL,
    origin_area VARCHAR(120) NULL,
    price DECIMAL(12,2) NOT NULL,
    unit VARCHAR(40) NOT NULL DEFAULT 'kg',
    stock INT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_products_seller FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_products_category (category),
    INDEX idx_products_seller (seller_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS orders (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id BIGINT UNSIGNED NULL,
    buyer_id BIGINT UNSIGNED NOT NULL,
    seller_id BIGINT UNSIGNED NULL,
    product_name VARCHAR(140) NOT NULL,
    quantity INT UNSIGNED NOT NULL,
    unit_price DECIMAL(12,2) NOT NULL,
    total_price DECIMAL(14,2) NOT NULL,
    status ENUM('Menunggu', 'Diproses', 'Dikirim', 'Selesai') NOT NULL DEFAULT 'Menunggu',
    shipping_note VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_orders_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL,
    CONSTRAINT fk_orders_buyer FOREIGN KEY (buyer_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_orders_seller FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_orders_buyer_date (buyer_id, created_at),
    INDEX idx_orders_seller_date (seller_id, created_at)
) ENGINE=InnoDB;
