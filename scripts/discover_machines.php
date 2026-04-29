#!/usr/bin/env php
<?php
/**
 * discover_machines.php — découverte mensuelle de nouvelles machines via Rainforest bestsellers.
 *
 * Logique :
 *  1. Scrape les bestsellers Amazon FR catégorie "Cafetières automatiques avec broyeur"
 *  2. Pour chaque ASIN absent de products :
 *     → ajouter en status='candidate' + source='rainforest_bestseller' + discovered_at=NOW
 *     → si rating ≥ 4.0 ET reviews ≥ 50 → promu en 'published'
 *     → sinon en 'candidate' (visible admin, pas affiché public)
 *  3. Pour chaque ASIN existant : update prev_rank, rank_delta (trend)
 *
 * Cron OVH (mensuel, 1er du mois 6h, AVANT discover_keywords) :
 *   0 6 1 * * /usr/local/php8.2/bin/php /home/.../scripts/discover_machines.php >> logs/discover.log 2>&1
 *
 * Budget Rainforest : ~50 crédits/mois (1 call bestsellers + N calls product pour les nouveautés)
 */

declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

use App\Core\Database;
use App\Core\RainforestClient;
use App\Core\ArticleGenerator;

// Catégorie Amazon FR : "Cafetières automatiques avec broyeur intégré"
// Note : si ce node ID change, ajuster ici. Voir https://www.amazon.fr/gp/bestsellers
const AMAZON_BESTSELLER_CATEGORY = 'kitchen';
const AMAZON_BESTSELLER_URL = 'https://www.amazon.fr/gp/bestsellers/kitchen/2851259031';

const PROMOTION_RATING_MIN = 4.0;
const PROMOTION_REVIEWS_MIN = 50;
const MAX_NEW_PRODUCTS_PER_RUN = 8; // garde-fou : on n'ajoute pas 50 produits d'un coup

$start = microtime(true);
echo sprintf("[%s] discover_machines.php starting\n", date('Y-m-d H:i:s'));

$stats = [
    'bestsellers_scanned' => 0,
    'new_candidates'      => 0,
    'auto_promoted'       => 0,
    'rank_updated'        => 0,
    'api_calls'           => 0,
    'errors'              => 0,
];

try {
    $rf = new RainforestClient();
    $pdo = Database::pdo();

    // ===== 1. Récupère les bestsellers Amazon FR =====
    echo sprintf("→ Scan bestsellers : %s\n", AMAZON_BESTSELLER_URL);
    $resp = callBestsellersByUrl($rf, AMAZON_BESTSELLER_URL);
    $stats['api_calls']++;

    $bestsellers = $resp['bestsellers'] ?? $resp['category_results'] ?? [];
    if (empty($bestsellers)) {
        echo "  × aucun bestseller retourné. Abort.\n";
        exit(0);
    }
    echo sprintf("  ✓ %d bestsellers retournés\n", count($bestsellers));

    $newCount = 0;
    foreach ($bestsellers as $bs) {
        $stats['bestsellers_scanned']++;
        $asin = (string)($bs['asin'] ?? '');
        if (strlen($asin) !== 10) continue;
        $rank = (int)($bs['rank'] ?? 0);

        // Existe déjà en BDD ?
        $stmt = $pdo->prepare("SELECT id, status, bestsellers_rank FROM products WHERE asin = ?");
        $stmt->execute([$asin]);
        $existing = $stmt->fetch();

        if ($existing) {
            // Update prev_rank + rank_delta + last_scanned_at
            $prev = (int)($existing['bestsellers_rank'] ?? 0);
            $delta = $prev > 0 ? ($prev - $rank) : 0;
            $pdo->prepare(
                "UPDATE products SET prev_rank = bestsellers_rank, bestsellers_rank = ?, rank_delta = ?, last_scanned_at = NOW() WHERE id = ?"
            )->execute([$rank, $delta, $existing['id']]);
            $stats['rank_updated']++;
            echo sprintf("  ~ existing ASIN %s rank=%d (delta=%+d)\n", $asin, $rank, $delta);
            continue;
        }

        // Nouveau produit : on tente l'ajout
        if ($newCount >= MAX_NEW_PRODUCTS_PER_RUN) {
            echo sprintf("  - skip (quota %d new products atteint)\n", MAX_NEW_PRODUCTS_PER_RUN);
            continue;
        }

        try {
            // Récupère la fiche complète pour évaluer rating/reviews
            echo sprintf("  · nouveau ASIN %s (rank=%d) → fetch product\n", $asin, $rank);
            $product = $rf->getProduct($asin);
            $stats['api_calls']++;

            $rating = isset($product['rating']) ? (float)$product['rating'] : 0;
            $reviews = isset($product['ratings_total']) ? (int)$product['ratings_total'] : 0;
            $title = mb_substr((string)($product['title'] ?? ''), 0, 280);
            $brand = (string)($product['brand'] ?? '');

            // Filtre : on ne veut que des cafetières à grain (broyeur intégré)
            if (!isCafetiereAGrain($title, $brand)) {
                echo sprintf("    - skip (titre ne matche pas cafetière à grain)\n");
                continue;
            }

            $autoPromote = ($rating >= PROMOTION_RATING_MIN && $reviews >= PROMOTION_REVIEWS_MIN);
            $status = $autoPromote ? 'published' : 'candidate';

            $slug = generateSlug($title, $brand, $asin);
            $price = isset($product['buybox_winner']['price']['value']) ? (float)$product['buybox_winner']['price']['value'] : null;
            $imageUrl = $product['main_image']['link'] ?? null;
            $imagesJson = isset($product['images']) ? json_encode(array_column((array)$product['images'], 'link')) : null;

            $pdo->prepare(
                "INSERT INTO products
                    (asin, slug, name, brand, type_cafetiere, price_eur, rating, ratings_total,
                     bestsellers_rank, main_image_url, images_json, status, source, discovered_at, last_scanned_at)
                 VALUES (?, ?, ?, ?, 'espresso_broyeur_auto', ?, ?, ?, ?, ?, ?, ?, 'rainforest_bestseller', NOW(), NOW())"
            )->execute([
                $asin, $slug, $title, $brand,
                $price, $rating, $reviews, $rank,
                $imageUrl, $imagesJson, $status,
            ]);

            $stats['new_candidates']++;
            if ($autoPromote) $stats['auto_promoted']++;
            $newCount++;
            echo sprintf("    + ajouté (status=%s, rating=%s/5 sur %d avis)\n", $status, $rating, $reviews);

        } catch (\Throwable $e) {
            $stats['errors']++;
            echo sprintf("    × erreur sur ASIN %s : %s\n", $asin, $e->getMessage());
        }
    }

    // Tracking conso
    for ($i = 0; $i < $stats['api_calls']; $i++) {
        ArticleGenerator::trackApiUsage('rainforest', 0.0);
    }

    // Log dans cron_log
    $pdo->prepare(
        "INSERT INTO cron_log (job_name, status, message, duration_ms) VALUES (?, ?, ?, ?)"
    )->execute([
        'discover_machines',
        $stats['errors'] > 0 ? 'error' : 'success',
        json_encode($stats),
        (int)((microtime(true) - $start) * 1000),
    ]);

    echo "\n=== DISCOVER MACHINES COMPLETED ===\n";
    foreach ($stats as $k => $v) echo sprintf("  %-22s : %d\n", $k, $v);
    echo sprintf("  Durée : %ss\n", round(microtime(true) - $start, 1));
    exit($stats['errors'] > 0 ? 2 : 0);

} catch (\Throwable $e) {
    fwrite(STDERR, sprintf("[%s] FATAL : %s\n", date('Y-m-d H:i:s'), $e->getMessage()));
    exit(1);
}

// ============= HELPERS =============

/**
 * Appelle Rainforest type=bestsellers via URL Amazon directe.
 */
function callBestsellersByUrl(RainforestClient $rf, string $url): array
{
    // RainforestClient n'a pas de méthode publique pour bestsellers ; on appelle via reflection sur call()
    // Plus simple : on construit l'appel direct via cURL en réutilisant la clé de config
    $cfg = require dirname(__DIR__) . '/config/config.php';
    $apiKey = $cfg['rainforest_api_key'] ?? '';
    if ($apiKey === '') throw new RuntimeException('Rainforest key absente');

    $params = [
        'api_key' => $apiKey,
        'type' => 'bestsellers',
        'url' => $url,
        'amazon_domain' => 'amazon.fr',
        'language' => 'fr_FR',
    ];

    $ch = curl_init('https://api.rainforestapi.com/request?' . http_build_query($params));
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 90,
    ]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($code !== 200) throw new RuntimeException("Rainforest bestsellers HTTP $code");
    $json = json_decode((string)$body, true);
    if (!is_array($json)) throw new RuntimeException('Rainforest bestsellers : JSON invalide');
    return $json;
}

/**
 * Vrai si le titre + marque indiquent une cafetière à grain (broyeur intégré).
 */
function isCafetiereAGrain(string $title, string $brand): bool
{
    $haystack = mb_strtolower($title . ' ' . $brand);
    // Mots positifs (broyeur)
    $positive = ['grain', 'broyeur', 'broyeuse', 'lattego', 'expresso automatique', 'expresso auto', 'magnifica', 'eletta', 'primadonna', 'evidence', 'picobaristo', 'barista express', 'caffeo'];
    // Mots négatifs (capsule, dosette, manuel)
    $negative = ['nespresso', 'dolce gusto', 'capsule', 'dosette', 'tassimo', 'senseo', 'percolateur', 'piston', 'italienne', 'moka', 'manuelle'];

    $hasPositive = false;
    foreach ($positive as $kw) if (str_contains($haystack, $kw)) { $hasPositive = true; break; }
    foreach ($negative as $kw) if (str_contains($haystack, $kw)) return false;

    return $hasPositive;
}

/**
 * Génère un slug propre depuis titre + marque + ASIN (fallback).
 */
function generateSlug(string $title, string $brand, string $asin): string
{
    $base = trim(($brand ? $brand . ' ' : '') . $title);
    if (function_exists('iconv')) {
        $tr = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $base);
        if ($tr !== false) $base = $tr;
    }
    $base = strtolower($base);
    $base = preg_replace('/[^a-z0-9]+/', '-', $base) ?? '';
    $base = trim($base, '-');
    $base = mb_substr($base, 0, 80) ?: 'produit';
    return $base . '-' . strtolower($asin);
}
