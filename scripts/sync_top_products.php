#!/usr/bin/env php
<?php
/**
 * sync_top_products.php — refresh QUOTIDIEN des top N produits.
 *
 * Cible : les produits avec le meilleur score_pertinence (top vente + top notes).
 * Évite le coût d'un full sync 21 produits chaque jour ; concentre le budget Rainforest
 * sur les machines qui drainent 80% des clics affiliation.
 *
 * Cron OVH (lundi à samedi, 6h) :
 *   0 6 * * 1-6 /usr/local/php8.2/bin/php /home/.../scripts/sync_top_products.php >> logs/sync.log 2>&1
 *
 * Le dimanche, c'est sync_amazon.php (full sync) qui prend le relais.
 *
 * Budget : top_daily_count crédits/jour × 6 jours/semaine = ~18-21 crédits/semaine
 *           Soit ~80 crédits/mois pour le top 3 quotidien.
 */

declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

use App\Core\Database;
use App\Core\RainforestClient;
use App\Core\ArticleGenerator;

$start = microtime(true);
echo sprintf("[%s] sync_top_products.php starting\n", date('Y-m-d H:i:s'));

// Si on est dimanche, on skip (le full sync hebdo prend le relais)
$dayOfWeek = (int)date('w'); // 0 = dimanche
if ($dayOfWeek === 0) {
    echo "→ Dimanche détecté : skip (full sync hebdo prend le relais via sync_amazon.php).\n";
    exit(0);
}

$cfg = require __DIR__ . '/../config/config.php';
$topCount = (int)($cfg['sync']['top_daily_count'] ?? 3);

$stats = ['refreshed' => 0, 'errors' => 0, 'credits' => 0];

try {
    $rf = new RainforestClient();
    $pdo = Database::pdo();

    // Sélection des top N produits par score_pertinence (rating × log(reviews))
    // Filtre : status=published, asin valide, dernière sync > 18h (anti double-call)
    $stmt = $pdo->prepare(
        "SELECT id, slug, asin, price_eur, last_sync_at FROM products
         WHERE status = 'published'
           AND asin IS NOT NULL AND asin != '' AND asin NOT LIKE '%X%'
           AND (last_sync_at IS NULL OR last_sync_at < DATE_SUB(NOW(), INTERVAL 18 HOUR))
         ORDER BY score_pertinence DESC
         LIMIT :lim"
    );
    $stmt->bindValue(':lim', $topCount, \PDO::PARAM_INT);
    $stmt->execute();
    $tops = $stmt->fetchAll();

    echo sprintf("→ %d produit(s) top a refresh (top_daily_count=%d)\n", count($tops), $topCount);

    foreach ($tops as $p) {
        try {
            echo sprintf("  · [%s] refresh ASIN=%s\n", $p['slug'], $p['asin']);
            $product = $rf->getProduct($p['asin']);
            $stats['credits']++;
            ArticleGenerator::trackApiUsage('rainforest', 0.0);

            $update = [
                'price_eur'     => isset($product['buybox_winner']['price']['value'])
                                    ? (float)$product['buybox_winner']['price']['value']
                                    : ($p['price_eur'] ?? null),
                'rating'        => isset($product['rating']) ? (float)$product['rating'] : null,
                'ratings_total' => isset($product['ratings_total']) ? (int)$product['ratings_total'] : null,
                'main_image_url' => $product['main_image']['link'] ?? null,
                'last_sync_at'  => date('Y-m-d H:i:s'),
            ];
            $update = array_filter($update, fn($v) => $v !== null);
            if (empty($update)) continue;

            $set = implode(' = ?, ', array_keys($update)) . ' = ?';
            $stmt = $pdo->prepare("UPDATE products SET $set WHERE id = ?");
            $stmt->execute([...array_values($update), $p['id']]);

            $stats['refreshed']++;
            echo sprintf("    ✓ prix=%s€ rating=%s\n", $update['price_eur'] ?? '?', $update['rating'] ?? '?');
        } catch (\Throwable $e) {
            $stats['errors']++;
            echo sprintf("    × erreur : %s\n", $e->getMessage());
        }
    }

    // Log dans cron_log
    $pdo->prepare(
        "INSERT INTO cron_log (job_name, status, message, duration_ms) VALUES (?, ?, ?, ?)"
    )->execute([
        'sync_top_products',
        $stats['errors'] > 0 ? 'error' : 'success',
        json_encode($stats),
        (int)((microtime(true) - $start) * 1000),
    ]);

    $elapsed = round(microtime(true) - $start, 1);
    echo sprintf("\n=== TOP SYNC COMPLETED ===\n  Refreshed : %d\n  Errors    : %d\n  Credits   : %d\n  Duration  : %ss\n",
        $stats['refreshed'], $stats['errors'], $stats['credits'], $elapsed);
    exit($stats['errors'] > 0 ? 2 : 0);

} catch (\Throwable $e) {
    fwrite(STDERR, sprintf("[%s] FATAL : %s\n", date('Y-m-d H:i:s'), $e->getMessage()));
    exit(1);
}
