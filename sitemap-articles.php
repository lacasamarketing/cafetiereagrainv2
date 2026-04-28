<?php
// Sub-sitemap : articles de blog cafetiereagrain.fr
// URLs : /{slug}/ a la racine (preserve SEO du WP existant)

declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

use App\Core\Layout;
use App\Core\Database;

$cfg = Layout::loadConfig();
$baseUrl = $cfg['base_url'] ?? 'https://cafetiereagrain.fr';

header('Content-Type: application/xml; charset=utf-8');
header('X-Robots-Tag: noindex');
header('Cache-Control: public, max-age=3600');

$articles = [];
try {
    $pdo = Database::pdo();
    $articles = $pdo->query(
        "SELECT slug, updated_at, publish_at FROM articles
         WHERE status = 'published'
         ORDER BY publish_at DESC LIMIT 5000"
    )->fetchAll();
} catch (Throwable $e) {}

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach ($articles as $a) {
    $lastmod = !empty($a['updated_at']) ? date('c', strtotime($a['updated_at'])) :
               (!empty($a['publish_at']) ? date('c', strtotime($a['publish_at'])) : date('c'));
    echo "  <url>\n";
    echo "    <loc>" . htmlspecialchars($baseUrl . '/' . $a['slug'], ENT_XML1 | ENT_QUOTES, 'UTF-8') . "</loc>\n";
    echo "    <lastmod>" . $lastmod . "</lastmod>\n";
    echo "    <changefreq>weekly</changefreq>\n";
    echo "    <priority>0.8</priority>\n";
    echo "  </url>\n";
}
echo '</urlset>' . "\n";
