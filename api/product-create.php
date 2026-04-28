<?php
// API endpoint : crée ou met à jour un produit (Bearer token auth)
// Usage POST : https://cafetiereagrain.fr/api/product-create.php
// Permet aux scheduled tasks Claude / agents externes d'ajouter des produits.

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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, ['error' => 'POST attendu.']);
}

// Auth Bearer token (même que /api/article-create.php)
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
    error_log('product-create auth failed from ' . ($_SERVER['REMOTE_ADDR'] ?? '?'));
    respond(401, ['error' => 'Token invalide.']);
}

// Parse body
$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data)) {
    respond(400, ['error' => 'Body JSON invalide.']);
}

foreach (['slug', 'name', 'asin'] as $f) {
    if (empty($data[$f])) {
        respond(400, ['error' => "Champ obligatoire manquant : $f"]);
    }
}

$slug = strtolower(preg_replace('/[^a-z0-9\-]/', '', strtolower((string)$data['slug'])));
$asin = strtoupper(preg_replace('/[^A-Z0-9]/', '', (string)$data['asin']));
if (strlen($asin) !== 10) {
    respond(400, ['error' => 'ASIN doit faire 10 caractères alphanumériques.']);
}

$allowed = [
    'slug' => $slug,
    'name' => mb_substr((string)$data['name'], 0, 200),
    'brand' => isset($data['brand']) ? mb_substr((string)$data['brand'], 0, 100) : null,
    'asin' => $asin,
    'amazon_url' => isset($data['amazon_url']) ? mb_substr((string)$data['amazon_url'], 0, 500) : null,
    'technology' => in_array($data['technology'] ?? '', ['co2','uv','propane','aspirant','solaire','larvaire','combine'], true) ? $data['technology'] : 'combine',
    'surface_m2' => isset($data['surface_m2']) && $data['surface_m2'] !== '' ? (int)$data['surface_m2'] : null,
    'noise_db' => isset($data['noise_db']) && $data['noise_db'] !== '' ? (float)str_replace(',', '.', (string)$data['noise_db']) : null,
    'autonomy_hours' => isset($data['autonomy_hours']) && $data['autonomy_hours'] !== '' ? (int)$data['autonomy_hours'] : null,
    'price_eur' => isset($data['price_eur']) && $data['price_eur'] !== '' ? (float)str_replace(',', '.', (string)$data['price_eur']) : null,
    'rating' => isset($data['rating']) && $data['rating'] !== '' && (float)$data['rating'] > 0 ? (float)str_replace(',', '.', (string)$data['rating']) : null,
    'reviews_count' => isset($data['reviews_count']) && $data['reviews_count'] !== '' ? (int)$data['reviews_count'] : null,
    'rank_global' => isset($data['rank_global']) && $data['rank_global'] !== '' ? (int)$data['rank_global'] : null,
    'badge' => isset($data['badge']) ? mb_substr((string)$data['badge'], 0, 60) : null,
    'target_use' => in_array($data['target_use'] ?? '', ['terrasse','jardin','chambre','tigre','pro'], true) ? $data['target_use'] : null,
    'verdict' => isset($data['verdict']) ? mb_substr((string)$data['verdict'], 0, 255) : null,
    'pitch' => isset($data['pitch']) ? mb_substr((string)$data['pitch'], 0, 5000) : null,
    'pros' => is_array($data['pros'] ?? null) ? json_encode(array_values($data['pros']), JSON_UNESCAPED_UNICODE) : null,
    'cons' => is_array($data['cons'] ?? null) ? json_encode(array_values($data['cons']), JSON_UNESCAPED_UNICODE) : null,
    'image_url' => isset($data['image_url']) ? mb_substr((string)$data['image_url'], 0, 500) : null,
    'gallery_images' => is_array($data['gallery_images'] ?? null)
        ? json_encode(
            array_values(array_filter(array_slice(array_map(
                fn($u) => is_string($u) ? mb_substr($u, 0, 500) : null,
                $data['gallery_images']
            ), 0, 8))),
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        )
        : null,
    'status' => in_array($data['status'] ?? '', ['draft','published','archived'], true) ? $data['status'] : 'published',
];

try {
    $pdo = Database::pdo();
    $stmt = $pdo->prepare('SELECT id FROM products WHERE slug = ? OR asin = ? LIMIT 1');
    $stmt->execute([$slug, $asin]);
    $existing = $stmt->fetch();

    if ($existing) {
        $toUpdate = array_filter($allowed, fn($v) => $v !== null);
        if (empty($toUpdate)) respond(400, ['error' => 'Rien à mettre à jour.']);
        $set = implode(' = ?, ', array_keys($toUpdate)) . ' = ?';
        $stmt = $pdo->prepare("UPDATE products SET $set WHERE id = ?");
        $stmt->execute([...array_values($toUpdate), (int)$existing['id']]);
        respond(200, ['action' => 'updated', 'product_id' => (int)$existing['id'], 'slug' => $slug, 'url' => 'https://cafetiereagrain.fr/tests/' . $slug]);
    } else {
        $cols = array_keys($allowed);
        $place = implode(', ', array_fill(0, count($cols), '?'));
        $stmt = $pdo->prepare('INSERT INTO products (' . implode(', ', $cols) . ') VALUES (' . $place . ')');
        $stmt->execute(array_values($allowed));
        respond(201, ['action' => 'created', 'product_id' => (int)$pdo->lastInsertId(), 'slug' => $slug, 'url' => 'https://cafetiereagrain.fr/tests/' . $slug]);
    }
} catch (Throwable $e) {
    error_log('product-create error: ' . $e->getMessage());
    respond(500, ['error' => $e->getMessage()]);
}
