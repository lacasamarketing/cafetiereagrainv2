<?php
// Sub-sitemap : pages statiques cafetiereagrain.fr

declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

use App\Core\Layout;
use App\Core\Database;

$cfg = Layout::loadConfig();
$baseUrl = $cfg['base_url'] ?? 'https://cafetiereagrain.fr';

header('Content-Type: application/xml; charset=utf-8');
header('X-Robots-Tag: noindex');
header('Cache-Control: public, max-age=3600');

$pages = [
    ['loc' => '/',                  'changefreq' => 'daily',   'priority' => '1.0'],
    ['loc' => '/blog',              'changefreq' => 'daily',   'priority' => '0.9'],
    ['loc' => '/quiz',              'changefreq' => 'monthly', 'priority' => '0.7'],
    ['loc' => '/mentions-legales',  'changefreq' => 'yearly',  'priority' => '0.3'],
];

// Categories editoriales
try {
    $pdo = Database::pdo();
    $cats = $pdo->query("SELECT slug FROM categories ORDER BY sort_order ASC")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($cats as $slug) {
        $pages[] = ['loc' => '/categorie/' . $slug, 'changefreq' => 'weekly', 'priority' => '0.8'];
    }
} catch (Throwable $e) {}

$now = date('c');
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach ($pages as $p) {
    echo "  <url>\n";
    echo "    <loc>" . htmlspecialchars($baseUrl . $p['loc'], ENT_XML1 | ENT_QUOTES, 'UTF-8') . "</loc>\n";
    echo "    <lastmod>" . $now . "</lastmod>\n";
    echo "    <changefreq>" . $p['changefreq'] . "</changefreq>\n";
    echo "    <priority>" . $p['priority'] . "</priority>\n";
    echo "  </url>\n";
}
echo '</urlset>' . "\n";
