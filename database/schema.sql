-- =====================================================
-- Schema DB cafetiereagrain.fr - Stack PHP + MySQL 8
-- =====================================================
-- A executer une seule fois sur la DB cible (via PHPMyAdmin OVH)
-- Apres : lancer scripts/import_data.php pour populer

SET NAMES utf8mb4;
SET time_zone = '+01:00';

-- =====================================================
-- TABLE PRODUCTS : cafetieres + accessoires Amazon enrichis
-- =====================================================
CREATE TABLE IF NOT EXISTS `products` (
    `id`                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `asin`              CHAR(10) NOT NULL UNIQUE,
    `slug`              VARCHAR(180) NOT NULL UNIQUE,
    `name`              VARCHAR(300) NOT NULL,
    `brand`             VARCHAR(80),
    `model_number`      VARCHAR(80),
    `type_cafetiere`    ENUM('espresso_broyeur_auto','espresso_manuel','expresso_capsule','hybride_grain_filtre','moulin','accessoire','autre') NOT NULL DEFAULT 'autre',
    `description_short` VARCHAR(400),
    `description_long`  TEXT,
    `price_eur`         DECIMAL(8,2),
    `price_old`         DECIMAL(8,2),
    `currency`          CHAR(3) NOT NULL DEFAULT 'EUR',
    `rating`            DECIMAL(3,2),
    `ratings_total`     INT UNSIGNED DEFAULT 0,
    `bestsellers_rank`  INT UNSIGNED,
    `bestsellers_cat`   VARCHAR(120),
    `amazons_choice`    TINYINT(1) NOT NULL DEFAULT 0,
    `color`             VARCHAR(80),
    `material`          VARCHAR(80),
    `capacity_l`        DECIMAL(4,2),
    `dimensions_cm`     VARCHAR(80),
    `weight_kg`         DECIMAL(5,2),
    `main_image_url`    VARCHAR(500),
    `images_json`       JSON,
    `specifications_json` JSON,
    `features_json`     JSON,
    `top_reviews_json`  JSON,
    `summary_avis`      TEXT COMMENT 'Synthese de 3-5 avis Amazon top helpful',
    `pros_json`         JSON,
    `cons_json`         JSON,
    `verdict`           VARCHAR(400),
    `score_pertinence`  DECIMAL(6,2) GENERATED ALWAYS AS (
        IF(rating IS NOT NULL AND ratings_total > 0,
           rating * LOG10(GREATEST(ratings_total, 1) + 1),
           0)
    ) STORED COMMENT 'Score auto = rating x log(reviews) pour TOP pertinence',
    `score_vente`       INT UNSIGNED GENERATED ALWAYS AS (
        IF(bestsellers_rank IS NOT NULL,
           99999 - LEAST(bestsellers_rank, 99999),
           0)
    ) STORED COMMENT 'Score auto inverse du rank Amazon pour TOP vente',
    `status`            ENUM('draft','published','archived') NOT NULL DEFAULT 'published',
    `created_at`        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_brand` (`brand`),
    INDEX `idx_type` (`type_cafetiere`),
    INDEX `idx_status` (`status`),
    INDEX `idx_score_pertinence` (`score_pertinence` DESC),
    INDEX `idx_score_vente` (`score_vente` DESC),
    INDEX `idx_price` (`price_eur`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLE ARTICLES : contenu editorial (importe depuis WP existant)
-- =====================================================
CREATE TABLE IF NOT EXISTS `articles` (
    `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `wp_id`           INT UNSIGNED COMMENT 'ID original WordPress (tracabilite migration)',
    `slug`            VARCHAR(190) NOT NULL UNIQUE,
    `title`           VARCHAR(300) NOT NULL,
    `description`     VARCHAR(400),
    `keyword_target`  VARCHAR(120),
    `cluster`         VARCHAR(60) NOT NULL DEFAULT 'general' COMMENT 'cafetiere-a-grain, cafes-en-grain, accessoires, conseils-budget',
    `persona`         VARCHAR(60),
    `content_html`    LONGTEXT NOT NULL,
    `content_summary` VARCHAR(600),
    `featured_image`  VARCHAR(500),
    `reading_time`    SMALLINT UNSIGNED DEFAULT 5,
    `status`          ENUM('draft','published','archived') NOT NULL DEFAULT 'published',
    `publish_at`      DATETIME,
    `views_count`     INT UNSIGNED NOT NULL DEFAULT 0,
    `created_at`      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_status_publish` (`status`, `publish_at` DESC),
    INDEX `idx_cluster` (`cluster`),
    INDEX `idx_wp_id` (`wp_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLE article_products : maillage articles <-> produits
-- =====================================================
CREATE TABLE IF NOT EXISTS `article_products` (
    `article_id`   INT UNSIGNED NOT NULL,
    `product_id`   INT UNSIGNED NOT NULL,
    `position`     SMALLINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '1=produit principal, 2-N=alternatives',
    `context_text` VARCHAR(400) COMMENT 'Extrait article qui mentionne le produit',
    PRIMARY KEY (`article_id`, `product_id`),
    INDEX `idx_product` (`product_id`),
    INDEX `idx_position` (`article_id`, `position`),
    CONSTRAINT `fk_ap_article` FOREIGN KEY (`article_id`) REFERENCES `articles`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_ap_product` FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLE categories : categories editoriales
-- =====================================================
CREATE TABLE IF NOT EXISTS `categories` (
    `slug`        VARCHAR(60) PRIMARY KEY,
    `name`        VARCHAR(120) NOT NULL,
    `description` VARCHAR(500),
    `icon`        VARCHAR(60),
    `sort_order`  SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `created_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `categories` (`slug`, `name`, `description`, `icon`, `sort_order`) VALUES
('cafetiere-a-grain',  'Cafetiere a grain',   'Tests et avis sur les machines expresso a grain : DeLonghi, Philips, Jura, Krups, Saeco.', '', 10),
('cafes-en-grain',     'Cafes en grain',      'Origines, varietes et torrefactions : Arabica, Robusta, cafe ethiopien, colombien, vietnamien.', '', 20),
('accessoires',        'Accessoires',         'Tasses, moulins, balances, accessoires barista pour ta cafetiere a grain.', '', 30),
('conseils-budget',    'Conseils et budget',  'Cout annuel, comparatifs grain vs dosette, choix entretien, problemes machine.', '', 40)
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`), `description`=VALUES(`description`);

-- =====================================================
-- TABLE redirects : redirection 301 anciennes URLs WP
-- =====================================================
CREATE TABLE IF NOT EXISTS `redirects` (
    `from_path` VARCHAR(255) PRIMARY KEY,
    `to_path`   VARCHAR(255) NOT NULL,
    `status`    SMALLINT UNSIGNED NOT NULL DEFAULT 301,
    `hits`      INT UNSIGNED NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLE clicks_log : tracking clics affilies (anonymise RGPD)
-- =====================================================
CREATE TABLE IF NOT EXISTS `clicks_log` (
    `id`           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `dest_slug`    VARCHAR(80) NOT NULL,
    `product_id`   INT UNSIGNED,
    `article_slug` VARCHAR(190),
    `referer`      VARCHAR(500),
    `user_agent`   VARCHAR(500),
    `ip_hash`      CHAR(64) COMMENT 'sha256 IP + salt (RGPD)',
    `clicked_at`   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_dest` (`dest_slug`, `clicked_at`),
    INDEX `idx_product` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLE cron_log : tracking jobs auto (sync amazon, etc.)
-- =====================================================
CREATE TABLE IF NOT EXISTS `cron_log` (
    `id`         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `job_name`   VARCHAR(80) NOT NULL,
    `status`     ENUM('success','error','skipped') NOT NULL,
    `message`    TEXT,
    `duration_ms` INT UNSIGNED,
    `executed_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_job_executed` (`job_name`, `executed_at` DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
