-- =================================================================
-- add_gallery_images_column.sql
-- Ajoute la colonne `gallery_images` (JSON) à la table products
-- pour stocker plusieurs photos Amazon par produit (Rainforest API).
-- =================================================================

SET NAMES utf8mb4;

ALTER TABLE products ADD COLUMN gallery_images JSON DEFAULT NULL AFTER image_url;
