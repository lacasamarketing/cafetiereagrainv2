<?php
// Sub-sitemap : fiches produits cafetiereagrain.fr
// URLs : /cafetiere/{asin}

declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

use App\Core\Layout;
use App\Core\Database;

$cfg = Layout::loadConfig();
$baseUrl = $cfg['base_url'] ?? 'https://cafetiereagrain.fr';

header('Content-Type: application/xml; charset=utf-8');
header('X-Robots-Tag: noindex');
header('Cache-Control: public, max-age=3600');

$products = [];
try {
    $pdo = Database::pdo();
    $products = $pdo->query(
        "SELECT asin, slug, updated_at FROM products
         WHERE status = 'published' AND asin IS NOT NULL
         ORDER BY score_pertinence DESC LIMIT 5000"
    )->fetchAll();
} catch (Throwable $e) {}

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach ($products as $p) {
    $lastmod = !empty($p['updated_at']) ? date('c', strtotime($p['updated_at'])) : date('c');
    echo "  <url>\n";
    echo "    <loc>" . htmlspecialchars($baseUrl . '/cafetiere/' . $p['asin'], ENT_XML1 | ENT_QUOTES, 'UTF-8') . "</loc>\n";
    echo "    <lastmod>" . $lastmod . "</lastmod>\n";
    echo "    <changefreq>weekly</changefreq>\n";
    echo "    <priority>0.7</priority>\n";
    echo "  </url>\n";
}
echo '</urlset>' . "\n";
