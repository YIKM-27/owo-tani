USE owo_tani;

ALTER TABLE farm_logs
    MODIFY activity ENUM('Penyiraman', 'Pemupukan', 'Penanganan cuaca ekstrem', 'Pengomposan') NOT NULL;

ALTER TABLE products
    MODIFY category ENUM('Benih', 'Pupuk', 'Alat tani', 'Hasil panen', 'Hasil panen sortiran', 'Lainnya') NOT NULL,
    ADD COLUMN origin_area VARCHAR(120) NULL AFTER image_path;

ALTER TABLE orders
    ADD COLUMN shipping_note VARCHAR(255) NULL AFTER status;
