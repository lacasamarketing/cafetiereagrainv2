<?php
// Génération d'un cover SVG pour un article (utilisé en og:image).
// URL : /blog/{slug}.svg → routé via .htaccess vers cover.php?slug={slug}

declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

use App\Core\Database;
use App\Core\CoverGenerator;

$slug = isset($_GET['slug']) ? preg_replace('/[^a-z0-9\-]/', '', strtolower((string)$_GET['slug'])) : '';
if ($slug === '') {
    http_response_code(404);
    exit;
}

try {
    $pdo = Database::pdo();
    $stmt = $pdo->prepare("SELECT title, cluster, reading_time FROM articles WHERE slug = ? AND status = 'published' LIMIT 1");
    $stmt->execute([$slug]);
    $a = $stmt->fetch();
} catch (Throwable $e) {
    $a = null;
}

if (!$a) {
    http_response_code(404);
    exit;
}

header('Content-Type: image/svg+xml; charset=UTF-8');
header('Cache-Control: public, max-age=2592000');
echo CoverGenerator::generate($a['title'], $a['cluster'] ?? 'general', (int)($a['reading_time'] ?? 5));
