<?php
// API endpoint : liste les produits existants (Bearer token auth)
// Usage GET : https://cafetiereagrain.fr/api/products-list.php
// Permet aux scheduled tasks de connaître les ASIN/slug déjà en BDD avant import.

declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

use App\Core\Database;

header('Content-Type: application/json; charset=UTF-8');
header('X-Content-Type-Options: nosniff');

function respond(int $code, array $data): void
{
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    respond(405, ['error' => 'GET attendu.']);
}

// Auth Bearer token
$config = Database::loadConfig();
$expectedToken = $config['api_token'] ?? '';
if (empty($expectedToken) || $expectedToken === 'CHANGE_ME') {
    respond(500, ['error' => 'API token non configuré côté serveur.']);
}

$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
if (!preg_match('/Bearer\s+(.+)/i', $authHeader, $m)) {
    respond(401, ['error' => 'Bearer token manquant.']);
}
if (!hash_equals($expectedToken, trim($m[1]))) {
    error_log('products-list auth failed from ' . ($_SERVER['REMOTE_ADDR'] ?? '?'));
    respond(401, ['error' => 'Token invalide.']);
}

try {
    $pdo = Database::pdo();
    $stmt = $pdo->query(
        "SELECT id, slug, name, brand, asin, technology, price_eur, rating, reviews_count, rank_global, status, image_url, gallery_images
         FROM products
         ORDER BY rank_global ASC, rating DESC"
    );
    $rows = $stmt->fetchAll();

    // Cast types pour JSON propre
    foreach ($rows as &$r) {
        $r['id'] = (int)$r['id'];
        $r['price_eur'] = $r['price_eur'] !== null ? (float)$r['price_eur'] : null;
        $r['rating'] = $r['rating'] !== null ? (float)$r['rating'] : null;
        $r['reviews_count'] = $r['reviews_count'] !== null ? (int)$r['reviews_count'] : null;
        $r['rank_global'] = $r['rank_global'] !== null ? (int)$r['rank_global'] : null;
        // gallery_images est stocké en JSON string -> on le décode pour la réponse
        if (!empty($r['gallery_images'])) {
            $decoded = json_decode((string)$r['gallery_images'], true);
            $r['gallery_images'] = is_array($decoded) ? $decoded : [];
        } else {
            $r['gallery_images'] = [];
        }
    }

    respond(200, [
        'count' => count($rows),
        'products' => $rows,
        'asins' => array_column($rows, 'asin'),
        'slugs' => array_column($rows, 'slug'),
    ]);
} catch (Throwable $e) {
    error_log('products-list error: ' . $e->getMessage());
    respond(500, ['error' => $e->getMessage()]);
}
