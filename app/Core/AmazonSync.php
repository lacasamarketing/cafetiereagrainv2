<?php
/**
 * AmazonSync — synchronisation complète des produits avec Amazon via Rainforest API.
 *
 * Champs récupérés et stockés (depuis getProduct) :
 *   - amazon_title (titre Amazon exact)        — info read-only
 *   - brand                                     — màj
 *   - price_eur                                 — màj systématique
 *   - rating (sur 5)                            — màj
 *   - reviews_count                             — màj
 *   - image_url (main image Amazon CDN)         — màj
 *   - features (JSON array, bullet points)      — màj
 *   - description (texte)                       — màj
 *   - is_prime                                  — màj
 *   - delivery_info                             — màj
 *   - in_stock                                  — màj
 *   - last_sync_at                              — màj
 *
 * Le `name` éditorial n'est PAS écrasé sauf au 1er fill (sur produit nouveau).
 */

declare(strict_types=1);

namespace App\Core;

use PDO;
use Throwable;

final class AmazonSync
{
    private RainforestClient $rf;
    private PDO $pdo;
    /** @var array<string,int> */
    public array $stats = [
        'placeholder_filled' => 0,
        'price_refreshed'    => 0,
        'discovered'         => 0,
        'errors'             => 0,
        'credits_estimated'  => 0,
    ];

    public function __construct(?RainforestClient $rf = null)
    {
        $this->rf = $rf ?? new RainforestClient();
        $this->pdo = Database::pdo();
    }

    public function run(array $opts = []): array
    {
        $discover = $opts['discover'] ?? false;
        $onlySlug = $opts['only_slug'] ?? null;
        $dryRun = $opts['dry_run'] ?? false;

        $this->fillPlaceholders($onlySlug, $dryRun);
        $this->refreshExisting($onlySlug, $dryRun);
        if ($discover) {
            $this->discover($dryRun);
        }
        return $this->stats;
    }

    /** Cherche l'ASIN par mot-clé puis récupère la fiche complète + écrit en BDD */
    private function fillPlaceholders(?string $onlySlug, bool $dryRun): void
    {
        $sql = "SELECT id, slug, name, brand, asin FROM products
                WHERE status = 'published'
                  AND (asin IS NULL OR asin = '' OR asin LIKE '%X%')";
        if ($onlySlug !== null) {
            $sql .= ' AND slug = ?';
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$onlySlug]);
        } else {
            $stmt = $this->pdo->query($sql);
        }
        foreach ($stmt->fetchAll() as $p) {
            try {
                $query = trim(($p['brand'] ? $p['brand'] . ' ' : '') . $p['name']);
                $results = $this->rf->searchProducts($query);
                $this->stats['credits_estimated']++;
                if (empty($results)) continue;
                $top = $results[0];
                $newAsin = $top['asin'] ?? null;
                if (!$newAsin) continue;

                // 2e appel : récupère la fiche complète
                $product = $this->rf->getProduct($newAsin);
                $this->stats['credits_estimated']++;

                $update = $this->extractFullUpdate($product, $newAsin);
                // Au 1er fill on accepte d'écraser le name si éditorial vide ou par défaut
                if (!empty($product['title']) && $this->shouldReplaceName($p['name'], $product['title'])) {
                    $update['name'] = mb_substr($product['title'], 0, 200);
                }
                if (!$dryRun) {
                    $this->applyUpdate((int)$p['id'], $update);
                }
                $this->stats['placeholder_filled']++;
            } catch (Throwable $e) {
                $this->stats['errors']++;
                error_log('AmazonSync fill error : ' . $e->getMessage());
            }
        }
    }

    /** Refresh hebdo : récupère la fiche complète et update tous les champs Amazon */
    private function refreshExisting(?string $onlySlug, bool $dryRun): void
    {
        $sql = "SELECT id, slug, asin, name FROM products
                WHERE status = 'published'
                  AND asin IS NOT NULL AND asin != '' AND asin NOT LIKE '%X%'
                  AND (last_sync_at IS NULL OR last_sync_at < DATE_SUB(NOW(), INTERVAL 7 DAY))";
        if ($onlySlug !== null) {
            $sql .= ' AND slug = ?';
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$onlySlug]);
        } else {
            $stmt = $this->pdo->query($sql);
        }
        foreach ($stmt->fetchAll() as $p) {
            try {
                $product = $this->rf->getProduct($p['asin']);
                $this->stats['credits_estimated']++;
                $update = $this->extractFullUpdate($product, (string)$p['asin']);
                // Au refresh on n'écrase JAMAIS le name (l'éditorial l'emporte)
                unset($update['name']);
                if (!$dryRun) {
                    $this->applyUpdate((int)$p['id'], $update);
                }
                $this->stats['price_refreshed']++;
            } catch (Throwable $e) {
                $this->stats['errors']++;
                error_log('AmazonSync refresh error : ' . $e->getMessage());
            }
        }
    }

    /** Extrait du JSON Rainforest tous les champs utiles à stocker. */
    private function extractFullUpdate(array $product, string $asin): array
    {
        $price = $product['buybox_winner']['price']['value']
              ?? $product['main_price']['value']
              ?? $product['price']['value']
              ?? null;

        $features = $product['feature_bullets'] ?? $product['features'] ?? null;
        if (is_array($features)) {
            // certaines structures sont [{text:'...'}, ...] vs ['...', '...']
            $features = array_values(array_map(
                fn($f) => is_array($f) ? (string)($f['text'] ?? $f['feature'] ?? '') : (string)$f,
                $features
            ));
            $features = array_values(array_filter($features, fn($s) => trim($s) !== ''));
        }

        $description = $product['description'] ?? $product['product_description'] ?? null;
        if (is_array($description)) $description = (string)($description['value'] ?? '');

        $brand = $product['brand'] ?? $product['manufacturer'] ?? null;

        $imageUrl = $product['main_image']['link']
                 ?? ($product['images'][0]['link'] ?? null)
                 ?? null;

        $isPrime = null;
        if (isset($product['buybox_winner']['is_prime'])) {
            $isPrime = (bool)$product['buybox_winner']['is_prime'] ? 1 : 0;
        }

        $delivery = null;
        if (!empty($product['buybox_winner']['fulfillment']['standard_delivery']['date'])) {
            $delivery = (string)$product['buybox_winner']['fulfillment']['standard_delivery']['date'];
        } elseif (!empty($product['buybox_winner']['shipping']['raw'])) {
            $delivery = (string)$product['buybox_winner']['shipping']['raw'];
        }

        $update = [
            'asin'          => $asin,
            'amazon_title'  => isset($product['title']) ? mb_substr((string)$product['title'], 0, 500) : null,
            'brand'         => $brand ? mb_substr((string)$brand, 0, 100) : null,
            'price_eur'     => $price !== null ? (float)$price : null,
            'rating'        => isset($product['rating']) ? (float)$product['rating'] : null,
            'reviews_count' => isset($product['ratings_total']) ? (int)$product['ratings_total'] : null,
            'image_url'     => $imageUrl,
            'features'      => is_array($features) && !empty($features) ? json_encode($features, JSON_UNESCAPED_UNICODE) : null,
            'description'   => $description ? mb_substr((string)$description, 0, 5000) : null,
            'is_prime'      => $isPrime,
            'delivery_info' => $delivery ? mb_substr($delivery, 0, 255) : null,
            'in_stock'      => isset($product['buybox_winner']) ? 1 : 0,
            'last_sync_at'  => date('Y-m-d H:i:s'),
        ];
        return $update;
    }

    /** Heuristique : remplacer le name uniquement si placeholder/évident */
    private function shouldReplaceName(?string $current, string $amazonTitle): bool
    {
        if ($current === null || trim($current) === '') return true;
        // Si le name actuel ressemble au titre Amazon (premiers mots), on ne touche pas
        $a = mb_strtolower(mb_substr($current, 0, 30));
        $b = mb_strtolower(mb_substr($amazonTitle, 0, 30));
        if (strpos($b, $a) === 0 || strpos($a, $b) === 0) return false;
        // Sinon on garde : on respecte l'éditorial existant
        return false;
    }

    /** Découverte de nouveaux produits via les keyword_target en queue. */
    private function discover(bool $dryRun): void
    {
        $stmt = $this->pdo->query("SELECT DISTINCT keyword_target FROM article_queue WHERE status = 'pending' ORDER BY priority DESC LIMIT 5");
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $kw) {
            try {
                $results = $this->rf->searchProducts($kw);
                $this->stats['credits_estimated']++;
                foreach (array_slice($results, 0, 3) as $r) {
                    $asin = $r['asin'] ?? null;
                    if (!$asin) continue;
                    $check = $this->pdo->prepare('SELECT id FROM products WHERE asin = ? LIMIT 1');
                    $check->execute([$asin]);
                    if ($check->fetchColumn()) continue;
                    $slug = strtolower(preg_replace('/[^a-z0-9\-]/', '', preg_replace('/\s+/', '-', strtolower((string)($r['title'] ?? $asin)))));
                    $slug = substr($slug, 0, 80) ?: ('produit-' . strtolower($asin));
                    if (!$dryRun) {
                        $stmt = $this->pdo->prepare(
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
                    $this->stats['discovered']++;
                }
            } catch (Throwable $e) {
                $this->stats['errors']++;
                error_log('AmazonSync discover error : ' . $e->getMessage());
            }
        }
    }

    private function applyUpdate(int $id, array $u): void
    {
        $u = array_filter($u, fn($v) => $v !== null);
        if (empty($u)) return;
        $set = implode(' = ?, ', array_keys($u)) . ' = ?';
        $stmt = $this->pdo->prepare("UPDATE products SET $set WHERE id = ?");
        $stmt->execute([...array_values($u), $id]);
    }
}
