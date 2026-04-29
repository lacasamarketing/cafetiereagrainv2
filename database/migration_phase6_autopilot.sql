-- =====================================================
-- Migration Phase 6 : Auto-rédaction articles + veille SEO
-- Compatible MySQL 8 standard (OVH cluster127). Idempotent.
-- =====================================================

SET NAMES utf8mb4;
SET time_zone = '+01:00';

-- Helper : ajoute une colonne SEULEMENT si elle n'existe pas
DROP PROCEDURE IF EXISTS p6_add_column_if_missing;
DELIMITER $$
CREATE PROCEDURE p6_add_column_if_missing(
    IN tbl VARCHAR(64),
    IN col VARCHAR(64),
    IN coldef TEXT
)
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = DATABASE() AND table_name = tbl AND column_name = col
    ) THEN
        SET @sql = CONCAT('ALTER TABLE `', tbl, '` ADD COLUMN ', coldef);
        PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
    END IF;
END$$
DELIMITER ;

-- Helper : ajoute un index UNIQUE seulement si absent
DROP PROCEDURE IF EXISTS p6_add_unique_if_missing;
DELIMITER $$
CREATE PROCEDURE p6_add_unique_if_missing(
    IN tbl VARCHAR(64),
    IN idx VARCHAR(64),
    IN cols VARCHAR(255)
)
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.statistics
        WHERE table_schema = DATABASE() AND table_name = tbl AND index_name = idx
    ) THEN
        SET @sql = CONCAT('ALTER TABLE `', tbl, '` ADD UNIQUE KEY `', idx, '` (', cols, ')');
        PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
    END IF;
END$$
DELIMITER ;

-- Helper : ajoute un index simple si absent
DROP PROCEDURE IF EXISTS p6_add_index_if_missing;
DELIMITER $$
CREATE PROCEDURE p6_add_index_if_missing(
    IN tbl VARCHAR(64),
    IN idx VARCHAR(64),
    IN cols VARCHAR(255)
)
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.statistics
        WHERE table_schema = DATABASE() AND table_name = tbl AND index_name = idx
    ) THEN
        SET @sql = CONCAT('ALTER TABLE `', tbl, '` ADD INDEX `', idx, '` (', cols, ')');
        PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
    END IF;
END$$
DELIMITER ;

-- =====================================================
-- 1) article_queue : anti-doublons + scoring veille
-- =====================================================
CALL p6_add_column_if_missing('article_queue', 'keyword_norm', 'VARCHAR(200) DEFAULT NULL AFTER keyword_target');
CALL p6_add_column_if_missing('article_queue', 'source', "ENUM('manual','mangools','gsc','rainforest','bestseller') DEFAULT 'manual' AFTER persona");
CALL p6_add_column_if_missing('article_queue', 'volume_monthly', 'INT UNSIGNED DEFAULT NULL AFTER source');
CALL p6_add_column_if_missing('article_queue', 'kd_score', 'TINYINT UNSIGNED DEFAULT NULL AFTER volume_monthly');
CALL p6_add_column_if_missing('article_queue', 'trend_score', 'DECIMAL(5,2) DEFAULT 0 AFTER kd_score');

UPDATE article_queue
SET keyword_norm = LOWER(
    REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(
        TRIM(keyword_target),
        'à','a'),'â','a'),'é','e'),'è','e'),'ê','e'),'î','i'),'ô','o')
)
WHERE keyword_norm IS NULL;

CALL p6_add_unique_if_missing('article_queue', 'uniq_keyword_norm', '`keyword_norm`');

-- =====================================================
-- 2) articles : signature sémantique pour anti-doublon de sujet
-- =====================================================
CALL p6_add_column_if_missing('articles', 'title_hash', 'CHAR(40) DEFAULT NULL AFTER keyword_target');
CALL p6_add_index_if_missing('articles', 'idx_title_hash', '`title_hash`');

UPDATE articles
SET title_hash = SHA1(LOWER(REPLACE(REPLACE(REPLACE(slug, '-vs-', '-'), '-de-', '-'), '-le-', '-')))
WHERE title_hash IS NULL;

-- =====================================================
-- 3) products : veille auto + statut découverte
-- =====================================================
ALTER TABLE products
    MODIFY COLUMN status ENUM('draft','published','archived','candidate','watch') NOT NULL DEFAULT 'published';

CALL p6_add_column_if_missing('products', 'source', "ENUM('manual','rainforest_bestseller','rainforest_mover','rainforest_search','manual_admin') DEFAULT 'manual_admin' AFTER status");
CALL p6_add_column_if_missing('products', 'discovered_at', 'DATETIME DEFAULT NULL AFTER source');
CALL p6_add_column_if_missing('products', 'last_scanned_at', 'DATETIME DEFAULT NULL AFTER discovered_at');
CALL p6_add_column_if_missing('products', 'prev_rank', 'INT UNSIGNED DEFAULT NULL AFTER bestsellers_rank');
CALL p6_add_column_if_missing('products', 'rank_delta', 'INT DEFAULT 0 AFTER prev_rank');

-- =====================================================
-- 4) article_machines_main : anti-doublon de paire de machines
-- =====================================================
CREATE TABLE IF NOT EXISTS article_machines_main (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    article_id INT UNSIGNED NOT NULL,
    asin_a CHAR(10) NOT NULL,
    asin_b CHAR(10) DEFAULT NULL,
    pair_type ENUM('single','versus','battle','roundup') NOT NULL DEFAULT 'single',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_pair (asin_a, asin_b),
    INDEX idx_article (article_id),
    INDEX idx_asin_a (asin_a),
    CONSTRAINT fk_amm_article FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 5) api_usage : tracking quota
-- =====================================================
CREATE TABLE IF NOT EXISTS api_usage (
    api_name VARCHAR(20) NOT NULL,
    period_date DATE NOT NULL,
    calls INT UNSIGNED NOT NULL DEFAULT 0,
    cost_eur DECIMAL(8,4) NOT NULL DEFAULT 0,
    last_call_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (api_name, period_date),
    INDEX idx_date (period_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 6) mangools_cache : cache 90j des recherches KWFinder
-- =====================================================
CREATE TABLE IF NOT EXISTS mangools_cache (
    keyword_norm VARCHAR(200) NOT NULL PRIMARY KEY,
    response_json LONGTEXT NOT NULL,
    fetched_at DATETIME NOT NULL,
    INDEX idx_fetched (fetched_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 7) Vidage queue moustiques + seed initial café
-- =====================================================
DELETE FROM article_queue WHERE status = 'pending' AND (
    keyword_target LIKE '%moustique%' OR
    keyword_target LIKE '%mosquit%' OR
    keyword_target LIKE '%piege%uv%' OR
    keyword_target LIKE '%biogents%' OR
    keyword_target LIKE '%co2%'
);

INSERT IGNORE INTO article_queue (keyword_target, keyword_norm, title_hint, cluster, persona, source, priority) VALUES
('delonghi magnifica evo vs philips 3200', 'delonghi magnifica evo vs philips 3200', 'DeLonghi Magnifica EVO vs Philips 3200 : le vrai duel des automatiques sous 600 €', 'comparatif', 'tous', 'manual', 10),
('delonghi magnifica s vs evo', 'delonghi magnifica s vs evo', 'DeLonghi Magnifica S vs EVO : faut-il payer 100 € de plus en 2026 ?', 'comparatif', 'tous', 'manual', 10),
('philips 3200 vs 5400', 'philips 3200 vs 5400', 'Philips LatteGo 3200 vs 5400 : la différence vaut-elle 250 € ?', 'comparatif', 'tous', 'manual', 10),
('krups evidence vs delonghi magnifica', 'krups evidence vs delonghi magnifica', 'Krups Evidence vs DeLonghi Magnifica : laquelle choisir vraiment ?', 'comparatif', 'tous', 'manual', 9),
('saeco picobaristo vs delonghi eletta', 'saeco picobaristo vs delonghi eletta', 'Saeco PicoBaristo vs DeLonghi Eletta : duel haut de gamme', 'comparatif', 'tous', 'manual', 8),
('jura e8 vs delonghi primadonna', 'jura e8 vs delonghi primadonna', 'Jura E8 vs DeLonghi Primadonna : laquelle pour un usage exigeant ?', 'comparatif', 'pro', 'manual', 8),
('sage barista express vs touch', 'sage barista express vs touch', 'Sage Barista Express vs Barista Touch : quelle vraie valeur ajoutée ?', 'comparatif', 'tous', 'manual', 7),
('cafetiere a grain delonghi vs philips', 'cafetiere a grain delonghi vs philips', 'Cafetière à grain DeLonghi vs Philips : quel constructeur choisir en 2026 ?', 'comparatif', 'tous', 'manual', 9),
('cafetiere a grain krups vs delonghi', 'cafetiere a grain krups vs delonghi', 'Cafetière à grain Krups vs DeLonghi : verdict après comparatif complet', 'comparatif', 'tous', 'manual', 8),
('meilleure cafetiere a grain 2026', 'meilleure cafetiere a grain 2026', 'Meilleure cafetière à grain 2026 : sélection des modèles vraiment fiables', 'guide', 'tous', 'manual', 10),
('cafetiere a grain pas cher', 'cafetiere a grain pas cher', 'Cafetière à grain pas chère : 6 modèles solides sous 400 €', 'guide', 'particulier', 'manual', 9),
('cafetiere a grain silencieuse', 'cafetiere a grain silencieuse', 'Cafetière à grain silencieuse : comprendre les dB et choisir le bon broyeur', 'guide', 'particulier', 'manual', 9),
('cafetiere a grain compacte', 'cafetiere a grain compacte', 'Cafetière à grain compacte : 5 machines pour petite cuisine', 'guide', 'particulier', 'manual', 8),
('cafetiere a grain pour cappuccino', 'cafetiere a grain pour cappuccino', 'Cafetière à grain pour cappuccino : LatteGo, buse vapeur ou carafe ?', 'guide', 'tous', 'manual', 9),
('cafetiere a grain meilleur rapport qualite prix', 'cafetiere a grain meilleur rapport qualite prix', 'Cafetière à grain : le meilleur rapport qualité-prix en 2026', 'guide', 'tous', 'manual', 10),
('comment choisir cafetiere a grain', 'comment choisir cafetiere a grain', 'Comment choisir sa cafetière à grain : 7 critères qui comptent vraiment', 'guide', 'tous', 'manual', 10),
('broyeur ceramique ou acier cafetiere', 'broyeur ceramique ou acier cafetiere', 'Broyeur céramique ou acier : quel choix pour ta cafetière à grain ?', 'guide', 'tous', 'manual', 7),
('test delonghi magnifica evo', 'test delonghi magnifica evo', 'Test DeLonghi Magnifica EVO : verdict après analyse complète', 'test', 'tous', 'manual', 9),
('test philips lattego 5400', 'test philips lattego 5400', 'Test Philips LatteGo 5400 : ce que valent vraiment ses 12 spécialités', 'test', 'tous', 'manual', 9),
('test philips lattego 3200', 'test philips lattego 3200', 'Test Philips LatteGo 3200 : la machine d''entrée qui mousse encore en 2026', 'test', 'tous', 'manual', 9),
('test krups evidence ecoluxe', 'test krups evidence ecoluxe', 'Test Krups Evidence Ecoluxe : compacité française face aux géants italiens', 'test', 'tous', 'manual', 7),
('test delonghi eletta explore', 'test delonghi eletta explore', 'Test DeLonghi Eletta Explore : l''hyper-équipée vaut-elle son prix ?', 'test', 'tous', 'manual', 7),
('test sage barista touch', 'test sage barista touch', 'Test Sage Barista Touch : la semi-pro à porte-filtre vaut-elle 1500 € ?', 'test', 'tous', 'manual', 6),
('test jura e8 cafetiere a grain', 'test jura e8 cafetiere a grain', 'Test Jura E8 : le suisse qui durcit la concurrence haut de gamme', 'test', 'pro', 'manual', 7),
('test melitta caffeo barista t smart', 'test melitta caffeo barista t smart', 'Test Melitta Caffeo Barista T Smart : l''intelligence allemande', 'test', 'tous', 'manual', 6);

-- =====================================================
-- 8) Initialisation api_usage pour ce mois
-- =====================================================
INSERT IGNORE INTO api_usage (api_name, period_date, calls) VALUES
    ('mangools', DATE_FORMAT(CURDATE(), '%Y-%m-01'), 0),
    ('rainforest', DATE_FORMAT(CURDATE(), '%Y-%m-01'), 0),
    ('anthropic', DATE_FORMAT(CURDATE(), '%Y-%m-01'), 0);

-- Cleanup
DROP PROCEDURE IF EXISTS p6_add_column_if_missing;
DROP PROCEDURE IF EXISTS p6_add_unique_if_missing;
DROP PROCEDURE IF EXISTS p6_add_index_if_missing;
