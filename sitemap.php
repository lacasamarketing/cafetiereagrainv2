<?php
// Sitemap INDEX (master) cafetiereagrain.fr
// /sitemap.xml -> liste les sub-sitemaps (pages + articles + products)

declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

use App\Core\Layout;

$cfg = Layout::loadConfig();
$baseUrl = $cfg['base_url'] ?? 'https://cafetiereagrain.fr';

header('Content-Type: application/xml; charset=utf-8');
header('X-Robots-Tag: noindex');
header('Cache-Control: public, max-age=3600');

$now = date('c');
$subs = [
    ['loc' => '/sitemap-pages.xml',    'lastmod' => $now],
    ['loc' => '/sitemap-articles.xml', 'lastmod' => $now],
    ['loc' => '/sitemap-products.xml', 'lastmod' => $now],
];

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach ($subs as $s) {
    echo "  <sitemap>\n";
    echo "    <loc>" . htmlspecialchars($baseUrl . $s['loc'], ENT_XML1 | ENT_QUOTES, 'UTF-8') . "</loc>\n";
    echo "    <lastmod>" . $s['lastmod'] . "</lastmod>\n";
    echo "  </sitemap>\n";
}
echo '</sitemapindex>' . "\n";
