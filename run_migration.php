<?php
/**
 * Endpoint web pour executer migration_phase6_autopilot via PDO.
 * Idempotent : catch silencieusement les "Duplicate column" (1060) et "Duplicate key" (1061).
 *
 * Usage : https://cafetiereagrain.fr/run_migration.php?token=XXX
 *
 * SUPPRIMER ce fichier apres usage.
 */
declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

use App\Core\Database;

header('Content-Type: text/plain; charset=utf-8');

$cfg = require __DIR__ . '/config/config.php';
$expected = $cfg['api_token'] ?? '';
$got = $_GET['token'] ?? '';
if ($expected === '' || $got !== $expected) {
    http_response_code(403);
    echo "Forbidden : ?token=API_TOKEN requis\n";
    exit;
}

$pdo = Database::pdo();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$stats = ['ok' => 0, 'skipped' => 0, 'failed' => 0];

function exec_safe(PDO $pdo, string $label, string $sql, array &$stats): void {
    try {
        $pdo->exec($sql);
        $stats['ok']++;
        echo "OK  : $label\n";
    } catch (PDOException $e) {
        $code = $e->errorInfo[1] ?? 0;
        // 1060 = Duplicate column, 1061 = Duplicate key, 1050 = Table exists
        if (in_array($code, [1060, 1061, 1050, 1091], true)) {
            $stats['skipped']++;
            echo "SKIP: $label (deja existant : $code)\n";
        } else {
            $stats['failed']++;
            echo "FAIL: $label : " . $e->getMessage() . "\n";
        }
    }
}

echo "=== Migration Phase 6 ===\n\n";

// 0) article_queue : table de base si absente
exec_safe($pdo, 'create article_queue',
    "CREATE TABLE IF NOT EXISTS article_queue (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        keyword_target VARCHAR(150) NOT NULL,
        title_hint VARCHAR(255) DEFAULT NULL,
        cluster VARCHAR(50) DEFAULT 'general',
        persona VARCHAR(50) DEFAULT 'tous',
        priority INT DEFAULT 5,
        status ENUM('pending','in_progress','done','failed') DEFAULT 'pending',
        notes TEXT DEFAULT NULL,
        article_id INT UNSIGNED DEFAULT NULL,
        attempts INT UNSIGNED DEFAULT 0,
        last_attempt_at DATETIME DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_status_priority (status, priority, created_at),
        CONSTRAINT fk_queue_article FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci", $stats);

// 1) article_queue : colonnes anti-doublons + scoring
exec_safe($pdo, 'add article_queue.keyword_norm',
    "ALTER TABLE article_queue ADD COLUMN keyword_norm VARCHAR(200) DEFAULT NULL AFTER keyword_target", $stats);
exec_safe($pdo, 'add article_queue.source',
    "ALTER TABLE article_queue ADD COLUMN source ENUM('manual','mangools','gsc','rainforest','bestseller') DEFAULT 'manual' AFTER persona", $stats);
exec_safe($pdo, 'add article_queue.volume_monthly',
    "ALTER TABLE article_queue ADD COLUMN volume_monthly INT UNSIGNED DEFAULT NULL AFTER source", $stats);
exec_safe($pdo, 'add article_queue.kd_score',
    "ALTER TABLE article_queue ADD COLUMN kd_score TINYINT UNSIGNED DEFAULT NULL AFTER volume_monthly", $stats);
exec_safe($pdo, 'add article_queue.trend_score',
    "ALTER TABLE article_queue ADD COLUMN trend_score DECIMAL(5,2) DEFAULT 0 AFTER kd_score", $stats);

$pdo->exec("UPDATE article_queue SET keyword_norm = LOWER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(TRIM(keyword_target),'a','a'),'a','a'),'e','e'),'e','e'),'e','e'),'i','i'),'o','o')) WHERE keyword_norm IS NULL");
echo "OK  : backfill keyword_norm\n"; $stats['ok']++;

exec_safe($pdo, 'unique article_queue.keyword_norm',
    "ALTER TABLE article_queue ADD UNIQUE KEY uniq_keyword_norm (keyword_norm)", $stats);

// 2) articles : title_hash
exec_safe($pdo, 'add articles.title_hash',
    "ALTER TABLE articles ADD COLUMN title_hash CHAR(40) DEFAULT NULL AFTER keyword_target", $stats);
exec_safe($pdo, 'index articles.title_hash',
    "ALTER TABLE articles ADD INDEX idx_title_hash (title_hash)", $stats);

$pdo->exec("UPDATE articles SET title_hash = SHA1(LOWER(REPLACE(REPLACE(REPLACE(slug, '-vs-', '-'), '-de-', '-'), '-le-', '-'))) WHERE title_hash IS NULL");
echo "OK  : backfill title_hash\n"; $stats['ok']++;

// 3) products : status enum + colonnes veille
exec_safe($pdo, 'modify products.status ENUM',
    "ALTER TABLE products MODIFY COLUMN status ENUM('draft','published','archived','candidate','watch') NOT NULL DEFAULT 'published'", $stats);
exec_safe($pdo, 'add products.source',
    "ALTER TABLE products ADD COLUMN source ENUM('manual','rainforest_bestseller','rainforest_mover','rainforest_search','manual_admin') DEFAULT 'manual_admin' AFTER status", $stats);
exec_safe($pdo, 'add products.discovered_at',
    "ALTER TABLE products ADD COLUMN discovered_at DATETIME DEFAULT NULL AFTER source", $stats);
exec_safe($pdo, 'add products.last_scanned_at',
    "ALTER TABLE products ADD COLUMN last_scanned_at DATETIME DEFAULT NULL AFTER discovered_at", $stats);
exec_safe($pdo, 'add products.prev_rank',
    "ALTER TABLE products ADD COLUMN prev_rank INT UNSIGNED DEFAULT NULL AFTER bestsellers_rank", $stats);
exec_safe($pdo, 'add products.rank_delta',
    "ALTER TABLE products ADD COLUMN rank_delta INT DEFAULT 0 AFTER prev_rank", $stats);

// 4) article_machines_main
exec_safe($pdo, 'create article_machines_main',
    "CREATE TABLE IF NOT EXISTS article_machines_main (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci", $stats);

// 5) api_usage
exec_safe($pdo, 'create api_usage',
    "CREATE TABLE IF NOT EXISTS api_usage (
        api_name VARCHAR(20) NOT NULL,
        period_date DATE NOT NULL,
        calls INT UNSIGNED NOT NULL DEFAULT 0,
        cost_eur DECIMAL(8,4) NOT NULL DEFAULT 0,
        last_call_at TIMESTAMP NULL DEFAULT NULL,
        PRIMARY KEY (api_name, period_date),
        INDEX idx_date (period_date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci", $stats);

// 6) mangools_cache
exec_safe($pdo, 'create mangools_cache',
    "CREATE TABLE IF NOT EXISTS mangools_cache (
        keyword_norm VARCHAR(200) NOT NULL PRIMARY KEY,
        response_json LONGTEXT NOT NULL,
        fetched_at DATETIME NOT NULL,
        INDEX idx_fetched (fetched_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci", $stats);

// 7) Vidage queue moustique + seed cafe
$deleted = $pdo->exec("DELETE FROM article_queue WHERE status = 'pending' AND (
    keyword_target LIKE '%moustique%' OR keyword_target LIKE '%mosquit%' OR
    keyword_target LIKE '%piege%uv%' OR keyword_target LIKE '%biogents%' OR
    keyword_target LIKE '%co2%')");
echo "OK  : vidage queue moustique ($deleted lignes)\n"; $stats['ok']++;

$seeds = [
    ['delonghi magnifica evo vs philips 3200','DeLonghi Magnifica EVO vs Philips 3200','comparatif',10],
    ['delonghi magnifica s vs evo','DeLonghi Magnifica S vs EVO','comparatif',10],
    ['philips 3200 vs 5400','Philips LatteGo 3200 vs 5400','comparatif',10],
    ['krups evidence vs delonghi magnifica','Krups Evidence vs DeLonghi Magnifica','comparatif',9],
    ['saeco picobaristo vs delonghi eletta','Saeco PicoBaristo vs DeLonghi Eletta','comparatif',8],
    ['jura e8 vs delonghi primadonna','Jura E8 vs DeLonghi Primadonna','comparatif',8],
    ['sage barista express vs touch','Sage Barista Express vs Touch','comparatif',7],
    ['cafetiere a grain delonghi vs philips','Cafetiere a grain DeLonghi vs Philips','comparatif',9],
    ['cafetiere a grain krups vs delonghi','Cafetiere a grain Krups vs DeLonghi','comparatif',8],
    ['meilleure cafetiere a grain 2026','Meilleure cafetiere a grain 2026','guide',10],
    ['cafetiere a grain pas cher','Cafetiere a grain pas chere','guide',9],
    ['cafetiere a grain silencieuse','Cafetiere a grain silencieuse','guide',9],
    ['cafetiere a grain compacte','Cafetiere a grain compacte','guide',8],
    ['cafetiere a grain pour cappuccino','Cafetiere a grain pour cappuccino','guide',9],
    ['cafetiere a grain meilleur rapport qualite prix','Meilleur rapport qualite-prix','guide',10],
    ['comment choisir cafetiere a grain','Comment choisir sa cafetiere a grain','guide',10],
    ['broyeur ceramique ou acier cafetiere','Broyeur ceramique ou acier','guide',7],
    ['test delonghi magnifica evo','Test DeLonghi Magnifica EVO','test',9],
    ['test philips lattego 5400','Test Philips LatteGo 5400','test',9],
    ['test philips lattego 3200','Test Philips LatteGo 3200','test',9],
    ['test krups evidence ecoluxe','Test Krups Evidence Ecoluxe','test',7],
    ['test delonghi eletta explore','Test DeLonghi Eletta Explore','test',7],
    ['test sage barista touch','Test Sage Barista Touch','test',6],
    ['test jura e8 cafetiere a grain','Test Jura E8','test',7],
    ['test melitta caffeo barista t smart','Test Melitta Caffeo Barista T Smart','test',6],
];
$ins = $pdo->prepare("INSERT IGNORE INTO article_queue (keyword_target, keyword_norm, title_hint, cluster, persona, source, priority) VALUES (?, ?, ?, ?, 'tous', 'manual', ?)");
$inserted = 0;
foreach ($seeds as $s) {
    $ins->execute([$s[0], $s[0], $s[1], $s[2], $s[3]]);
    if ($ins->rowCount() > 0) $inserted++;
}
echo "OK  : seed cafe ($inserted nouveaux mots-cles inseres)\n"; $stats['ok']++;

// 8) Init api_usage
$pdo->exec("INSERT IGNORE INTO api_usage (api_name, period_date, calls) VALUES
    ('mangools', DATE_FORMAT(CURDATE(), '%Y-%m-01'), 0),
    ('rainforest', DATE_FORMAT(CURDATE(), '%Y-%m-01'), 0),
    ('anthropic', DATE_FORMAT(CURDATE(), '%Y-%m-01'), 0)");
echo "OK  : init api_usage\n"; $stats['ok']++;

echo "\n=== Done ===\n";
echo "OK      : {$stats['ok']}\n";
echo "Skipped : {$stats['skipped']} (deja existants)\n";
echo "Failed  : {$stats['failed']}\n";
echo "\n>>> SUPPRIME run_migration.php apres usage <<<\n";
