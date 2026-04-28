<?php
// Sitemap XML dynamique : pages statiques + articles + fiches produits + clusters blog
// URL publique : /sitemap.xml (route via .htaccess vers sitemap.php)

declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

use App\Core\Database;
use App\Core\Layout;

$cfg = Layout::loadConfig();
$base = rtrim($cfg['base_url'] ?? 'https://cafetiereagrain.fr', '/');
$today = date('Y-m-d');

$urls = [];

// Pages statiques principales
$urls[] = ['loc' => $base . '/',                 'lastmod' => $today, 'changefreq' => 'weekly',  'priority' => '1.0'];
$urls[] = ['loc' => $base . '/blog',             'lastmod' => $today, 'changefreq' => 'daily',   'priority' => '0.9'];
$urls[] = ['loc' => $base . '/mentions-legales', 'lastmod' => '2026-04-28', 'changefreq' => 'yearly', 'priority' => '0.2'];

// Pages cluster blog
$clusters = ['comparatif', 'guide', 'test', 'saison', 'usage'];
foreach ($clusters as $c) {
    $urls[] = ['loc' => $base . '/blog?cluster=' . $c, 'lastmod' => $today, 'changefreq' => 'weekly', 'priority' => '0.7'];
}

// Pages quiz interactifs
$urls[] = ['loc' => $base . '/quiz',          'lastmod' => $today, 'changefreq' => 'monthly', 'priority' => '0.8'];
$urls[] = ['loc' => $base . '/quiz/terrasse', 'lastmod' => $today, 'changefreq' => 'monthly', 'priority' => '0.8'];
$urls[] = ['loc' => $base . '/quiz/jardin',   'lastmod' => $today, 'changefreq' => 'monthly', 'priority' => '0.8'];
$urls[] = ['loc' => $base . '/quiz/chambre',  'lastmod' => $today, 'changefreq' => 'monthly', 'priority' => '0.8'];
$urls[] = ['loc' => $base . '/quiz/tigre',    'lastmod' => $today, 'changefreq' => 'monthly', 'priority' => '0.8'];

// Articles publies
try {
    $pdo = Database::pdo();
    $articles = $pdo->query(
        "SELECT slug, COALESCE(updated_at, publish_at, created_at) AS lastmod
         FROM articles WHERE status = 'published' ORDER BY publish_at DESC LIMIT 1000"
    )->fetchAll();
    foreach ($articles as $a) {
        $urls[] = [
            'loc' => $base . '/blog/' . $a['slug'],
            'lastmod' => substr((string)$a['lastmod'], 0, 10),
            'changefreq' => 'monthly',
            'priority' => '0.8',
        ];
    }

    // Fiches produits publiees
    $products = $pdo->query(
        "SELECT slug, COALESCE(updated_at, created_at) AS lastmod
         FROM products WHERE status = 'published' ORDER BY rank_global ASC LIMIT 500"
    )->fetchAll();
    foreach ($products as $p) {
        $urls[] = [
            'loc' => $base . '/tests/' . $p['slug'],
            'lastmod' => substr((string)$p['lastmod'], 0, 10),
            'changefreq' => 'monthly',
            'priority' => '0.9',
        ];
    }
} catch (Throwable $e) {
    // BDD pas encore importee : on sert au moins les pages statiques
}

header('Content-Type: application/xml; charset=UTF-8');
header('Cache-Control: public, max-age=3600');

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach ($urls as $u) {
    echo "  <url>\n";
    echo "    <loc>" . htmlspecialchars($u['loc'], ENT_QUOTES | ENT_XML1, 'UTF-8') . "</loc>\n";
    echo "    <lastmod>" . $u['lastmod'] . "</lastmod>\n";
    echo "    <changefreq>" . $u['changefreq'] . "</changefreq>\n";
    echo "    <priority>" . $u['priority'] . "</priority>\n";
    echo "  </url>\n";
}
echo '</urlset>' . "\n";
