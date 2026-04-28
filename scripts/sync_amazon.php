#!/usr/bin/env php
<?php
/**
 * sync_amazon.php — synchronise les produits avec Amazon via Rainforest API
 *
 * Logique :
 *  1. Pour chaque produit avec ASIN placeholder (contient 'X') ou vide :
 *     → Recherche par "name + brand" sur amazon.fr
 *     → Prend le top 1, update ASIN + image_url + price_eur (si à 0) + rating
 *  2. Pour les produits avec ASIN valide et last_sync_at > 7 jours :
 *     → Refresh prix + rating + stock via getOffers (plus économe en crédits)
 *
 * Usage :
 *   php scripts/sync_amazon.php             # sync standard (placeholders + refresh hebdo)
 *   php scripts/sync_amazon.php --discover  # cherche aussi de nouveaux produits via les keyword_target de la queue
 *   php scripts/sync_amazon.php --slug=mon-slug   # sync un seul produit
 *   php scripts/sync_amazon.php --dry-run   # simulation, n'écrit rien
 *
 * Cron OVH conseillé : 1×/semaine (dimanche 5h)
 *   /usr/local/php8.2/bin/php /home/.../scripts/sync_amazon.php >> logs/sync.log 2>&1
 */

declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

use App\Core\Database;
use App\Core\RainforestClient;
use App\Core\AmazonSync;

$args = $_SERVER['argv'] ?? [];
$flagDryRun = in_array('--dry-run', $args, true);
$flagDiscover = in_array('--discover', $args, true);
$onlySlug = null;
foreach ($args as $a) {
    if (strpos($a, '--slug=') === 0) {
        $onlySlug = substr($a, 7);
    }
}

$start = microtime(true);
echo sprintf("[%s] sync_amazon.php starting (dry_run=%d discover=%d only=%s)\n",
    date('Y-m-d H:i:s'), $flagDryRun ? 1 : 0, $flagDiscover ? 1 : 0, $onlySlug ?: '*');

try {
    $rf = new RainforestClient();
    $pdo = Database::pdo();

    $stats = ['placeholder_filled' => 0, 'price_refreshed' => 0, 'discovered' => 0, 'errors' => 0, 'credits_estimated' => 0];

    // ===== 1. Produits avec ASIN placeholder (contient X) ou NULL =====
    $sql = "SELECT id, slug, name, brand, asin, price_eur, image_url FROM products
            WHERE status = 'published'
              AND (asin IS NULL OR asin = '' OR asin LIKE '%X%')";
    if ($onlySlug !== null) {
        $sql .= " AND slug = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$onlySlug]);
    } else {
        $stmt = $pdo->query($sql);
    }
    $toFill = $stmt->fetchAll();
    echo sprintf("→ %d produit(s) avec ASIN placeholder à remplir\n", count($toFill));

    foreach ($toFill as $p) {
        try {
            $query = trim(($p['brand'] ? $p['brand'] . ' ' : '') . $p['name']);
            echo sprintf("  · [%s] recherche : \"%s\"\n", $p['slug'], $query);
            $results = $rf->searchProducts($query);
            $stats['credits_estimated']++;
            if (empty($results)) {
                echo sprintf("    × aucun résultat Amazon\n");
                continue;
            }
            $top = $results[0];
            $newAsin = $top['asin'] ?? null;
            if (!$newAsin) {
                echo sprintf("    × résultat sans ASIN, skip\n");
                continue;
            }
            $update = [
                'asin'         => $newAsin,
                'image_url'    => $top['image'] ?? $p['image_url'],
                'rating'       => $top['rating'] ?? null,
                'reviews_count'=> $top['ratings_total'] ?? null,
                'price_eur'    => (float)($p['price_eur']) > 0 ? $p['price_eur'] : (float)($top['price']['value'] ?? 0),
                'last_sync_at' => date('Y-m-d H:i:s'),
                'in_stock'     => 1,
            ];
            echo sprintf("    ✓ trouvé ASIN=%s rating=%s prix=%s€\n",
                $newAsin, $update['rating'] ?? '?', $update['price_eur'] ?? '?');
            if (!$flagDryRun) {
                applyUpdate($pdo, (int)$p['id'], $update);
            }
            $stats['placeholder_filled']++;
        } catch (Throwable $e) {
            $stats['errors']++;
            echo sprintf("    × erreur : %s\n", $e->getMessage());
        }
    }

    // ===== 2. Refresh hebdo des produits avec ASIN valide =====
    $refreshSql = "SELECT id, slug, asin, price_eur, last_sync_at FROM products
                   WHERE status = 'published'
                     AND asin IS NOT NULL AND asin != '' AND asin NOT LIKE '%X%'
                     AND (last_sync_at IS NULL OR last_sync_at < DATE_SUB(NOW(), INTERVAL 7 DAY))";
    if ($onlySlug !== null) {
        $refreshSql .= " AND slug = ?";
        $stmt = $pdo->prepare($refreshSql);
        $stmt->execute([$onlySlug]);
    } else {
        $stmt = $pdo->query($refreshSql);
    }
    $toRefresh = $stmt->fetchAll();
    echo sprintf("→ %d produit(s) à refresh (sync > 7 jours)\n", count($toRefresh));

    foreach ($toRefresh as $p) {
        try {
            echo sprintf("  · [%s] refresh ASIN=%s\n", $p['slug'], $p['asin']);
            $product = $rf->getProduct($p['asin']);
            $stats['credits_estimated']++;
            $update = [
                'price_eur'    => isset($product['buybox_winner']['price']['value']) ? (float)$product['buybox_winner']['price']['value'] : ($p['price_eur'] ?? null),
                'rating'       => isset($product['rating']) ? (float)$product['rating'] : null,
                'reviews_count'=> isset($product['ratings_total']) ? (int)$product['ratings_total'] : null,
                'image_url'    => $product['main_image']['link'] ?? null,
                'in_stock'     => isset($product['buybox_winner']) ? 1 : 0,
                'last_sync_at' => date('Y-m-d H:i:s'),
            ];
            echo sprintf("    ✓ prix=%s€ rating=%s in_stock=%d\n",
                $update['price_eur'] ?? '?', $update['rating'] ?? '?', $update['in_stock']);
            if (!$flagDryRun) {
                applyUpdate($pdo, (int)$p['id'], $update);
            }
            $stats['price_refreshed']++;
        } catch (Throwable $e) {
            $stats['errors']++;
            echo sprintf("    × erreur : %s\n", $e->getMessage());
        }
    }

    // ===== 3. (Optionnel) Découverte : ajoute des nouveaux produits depuis la queue =====
    if ($flagDiscover) {
        $keywords = $pdo->query("SELECT DISTINCT keyword_target FROM article_queue WHERE status = 'pending' ORDER BY priority DESC LIMIT 5")->fetchAll(\PDO::FETCH_COLUMN);
        echo sprintf("→ Discovery : %d mot(s)-clé(s) à explorer\n", count($keywords));
        foreach ($keywords as $kw) {
            try {
                echo sprintf("  · search \"%s\"\n", $kw);
                $results = $rf->searchProducts($kw);
                $stats['credits_estimated']++;
                $count = 0;
                foreach (array_slice($results, 0, 3) as $r) {
                    $asin = $r['asin'] ?? null;
                    if (!$asin) continue;
                    // skip si ASIN déjà en BDD
                    $check = $pdo->prepare('SELECT id FROM products WHERE asin = ? LIMIT 1');
                    $check->execute([$asin]);
                    if ($check->fetchColumn()) continue;
                    $slug = strtolower(preg_replace('/[^a-z0-9\-]/', '', preg_replace('/\s+/', '-', strtolower($r['title'] ?? $asin))));
                    $slug = substr($slug, 0, 80) ?: ('produit-' . strtolower($asin));
                    if (!$flagDryRun) {
                        $stmt = $pdo->prepare(
                            "INSERT INTO products (slug, name, brand, asin, technology, price_eur, rating, reviews_count, image_url, status, last_sync_at)
                             VALUES (?, ?, ?, ?, 'combine', ?, ?, ?, ?, 'draft', NOW())"
                        );
                        $stmt->execute([
                            $slug,
                            mb_substr((string)($r['title'] ?? ''), 0, 200),
                            $r['brand'] ?? null,
                            $asin,
                            (float)($r['price']['value'] ?? 0),
                            $r['rating'] ?? null,
                            $r['ratings_total'] ?? null,
                            $r['image'] ?? null,
                        ]);
                    }
                    echo sprintf("    + nouveau: %s [%s]\n", $r['title'] ?? '?', $asin);
                    $count++;
                    $stats['discovered']++;
                }
                echo sprintf("    → %d nouveau(x) ajouté(s) en draft\n", $count);
            } catch (Throwable $e) {
                $stats['errors']++;
                echo sprintf("    × erreur : %s\n", $e->getMessage());
            }
        }
    }

    $elapsed = round(microtime(true) - $start, 1);
    echo "\n=== SYNC COMPLETED ===\n";
    echo sprintf("  Placeholders remplis    : %d\n", $stats['placeholder_filled']);
    echo sprintf("  Refresh prix/stock      : %d\n", $stats['price_refreshed']);
    if ($flagDiscover) echo sprintf("  Nouveaux produits       : %d\n", $stats['discovered']);
    echo sprintf("  Erreurs                 : %d\n", $stats['errors']);
    echo sprintf("  Crédits Rainforest util.: ~%d\n", $stats['credits_estimated']);
    echo sprintf("  Durée                   : %ss\n", $elapsed);
    if ($flagDryRun) echo "  ⚠ DRY RUN — aucune écriture en BDD\n";
    exit($stats['errors'] > 0 ? 2 : 0);

} catch (Throwable $e) {
    fwrite(STDERR, sprintf("[%s] FATAL : %s\n", date('Y-m-d H:i:s'), $e->getMessage()));
    exit(1);
}

function applyUpdate(\PDO $pdo, int $id, array $u): void
{
    $u = array_filter($u, fn($v) => $v !== null);
    if (empty($u)) return;
    $set = implode(' = ?, ', array_keys($u)) . ' = ?';
    $stmt = $pdo->prepare("UPDATE products SET $set WHERE id = ?");
    $stmt->execute([...array_values($u), $id]);
}
