USE owo_tani;

ALTER TABLE users
    ADD COLUMN avatar_path VARCHAR(255) NULL AFTER role;

ALTER TABLE products
    ADD COLUMN image_path VARCHAR(255) NULL AFTER description;

ALTER TABLE farms
    ADD COLUMN location_address VARCHAR(700) NULL AFTER longitude,
    ADD COLUMN bmkg_adm4 VARCHAR(20) NULL AFTER location_address;
