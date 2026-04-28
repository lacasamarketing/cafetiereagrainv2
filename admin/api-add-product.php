<?php
/**
 * Endpoint admin : ajoute ou met à jour un produit en BDD via JSON.
 * Auth : session admin (pas de Bearer externe nécessaire).
 *
 * Usage côté Claude in Chrome (depuis l'admin connecté) :
 *   fetch('/admin/api-add-product.php', {
 *     method: 'POST',
 *     headers: {'Content-Type': 'application/json'},
 *     body: JSON.stringify({slug, name, brand, asin, ...})
 *   }).then(r => r.json())
 *
 * Comportement :
 *   - INSERT si le slug n'existe pas
 *   - UPDATE si le slug existe déjà (idempotent)
 *   - Tous les champs sont optionnels sauf slug/name/asin
 */

declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

use App\Core\Auth;
use App\Core\Database;

header('Content-Type: application/json; charset=UTF-8');
header('X-Content-Type-Options: nosniff');

function jrespond(int $code, array $data): void
{
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit;
}

Auth::startSession();
if (!Auth::isLoggedIn()) {
    jrespond(401, ['error' => 'Connexion admin requise.']);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jrespond(405, ['error' => 'POST attendu.']);
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data)) {
    jrespond(400, ['error' => 'Body JSON invalide.']);
}

foreach (['slug', 'name', 'asin'] as $required) {
    if (empty($data[$required])) {
        jrespond(400, ['error' => "Champ obligatoire manquant : $required"]);
    }
}

$slug = strtolower(preg_replace('/[^a-z0-9\-]/', '', strtolower((string)$data['slug'])));
$asin = strtoupper(preg_replace('/[^A-Z0-9]/', '', (string)$data['asin']));
if (strlen($asin) !== 10) {
    jrespond(400, ['error' => 'ASIN doit faire 10 caractères alphanumériques.']);
}

// Champs autorisés à l'écriture
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
    'status' => in_array($data['status'] ?? '', ['draft', 'published', 'archived'], true) ? $data['status'] : 'published',
];

try {
    $pdo = Database::pdo();

    // Check si le slug existe déjà → UPDATE, sinon INSERT
    $stmt = $pdo->prepare('SELECT id FROM products WHERE slug = ? LIMIT 1');
    $stmt->execute([$slug]);
    $existing = $stmt->fetch();

    if ($existing) {
        // UPDATE — on garde les valeurs existantes pour tout ce qui est NULL en entrée
        $toUpdate = array_filter($allowed, fn($v) => $v !== null);
        if (empty($toUpdate)) {
            jrespond(400, ['error' => 'Aucun champ à mettre à jour.']);
        }
        $set = implode(' = ?, ', array_keys($toUpdate)) . ' = ?';
        $stmt = $pdo->prepare("UPDATE products SET $set WHERE id = ?");
        $stmt->execute([...array_values($toUpdate), (int)$existing['id']]);
        jrespond(200, [
            'action' => 'updated',
            'product_id' => (int)$existing['id'],
            'slug' => $slug,
            'url' => '/tests/' . $slug,
        ]);
    } else {
        // INSERT
        $cols = array_keys($allowed);
        $place = implode(', ', array_fill(0, count($cols), '?'));
        $stmt = $pdo->prepare('INSERT INTO products (' . implode(', ', $cols) . ') VALUES (' . $place . ')');
        $stmt->execute(array_values($allowed));
        $newId = (int)$pdo->lastInsertId();
        jrespond(201, [
            'action' => 'created',
            'product_id' => $newId,
            'slug' => $slug,
            'url' => '/tests/' . $slug,
        ]);
    }
} catch (Throwable $e) {
    jrespond(500, ['error' => $e->getMessage()]);
}
