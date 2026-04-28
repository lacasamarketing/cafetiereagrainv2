<?php
// API endpoint : retourne le prochain mot-cle a traiter depuis la queue.
// Auth : Bearer token.
// Usage : GET https://redactionavecia.fr/api/next-topic.php

declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

use App\Core\Database;
use App\Core\Queue;

header('Content-Type: application/json; charset=UTF-8');
header('X-Content-Type-Options: nosniff');

function respond(int $code, array $data): void
{
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    respond(405, ['error' => 'Method not allowed. Use GET.']);
}

// Auth
$config = Database::loadConfig();
$expectedToken = $config['api_token'] ?? '';
if (empty($expectedToken) || $expectedToken === 'CHANGE_ME') {
    respond(500, ['error' => 'API token non configure.']);
}

$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
if (!preg_match('/Bearer\s+(.+)/i', $authHeader, $m)) {
    respond(401, ['error' => 'Missing Bearer token.']);
}
if (!hash_equals($expectedToken, trim($m[1]))) {
    respond(401, ['error' => 'Invalid token.']);
}

try {
    $topic = Queue::pickNext();
    if (!$topic) {
        respond(404, ['error' => 'Queue vide. Ajoute des mots-cles via /admin/queue.php']);
    }

    respond(200, [
        'queue_id'       => (int)$topic['id'],
        'keyword_target' => $topic['keyword_target'],
        'title_hint'     => $topic['title_hint'],
        'cluster'        => $topic['cluster'],
        'persona'        => $topic['persona'],
        'priority'       => (int)$topic['priority'],
    ]);
} catch (\Throwable $e) {
    error_log('API next-topic error: ' . $e->getMessage());
    respond(500, ['error' => 'Server error.']);
}
