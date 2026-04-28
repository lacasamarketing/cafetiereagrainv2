<?php
// API endpoint : recoit un article JSON et le publie.
// Auth : Bearer token dans header Authorization.
// Usage : POST https://cafetiereagrain.fr/api/article-create.php

declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

use App\Core\Database;
use App\Core\Article;
use App\Core\Queue;

header('Content-Type: application/json; charset=UTF-8');
header('X-Content-Type-Options: nosniff');

function respond(int $code, array $data): void
{
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// Method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, ['error' => 'Method not allowed. Use POST.']);
}

// Authentication
$config = Database::loadConfig();
$expectedToken = $config['api_token'] ?? '';
if (empty($expectedToken) || $expectedToken === 'CHANGE_ME') {
    respond(500, ['error' => 'API token non configure cote serveur.']);
}

$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
if (!preg_match('/Bearer\s+(.+)/i', $authHeader, $m)) {
    respond(401, ['error' => 'Missing Bearer token.']);
}
$providedToken = trim($m[1]);
if (!hash_equals($expectedToken, $providedToken)) {
    error_log('API auth failed from ' . ($_SERVER['REMOTE_ADDR'] ?? '?'));
    respond(401, ['error' => 'Invalid token.']);
}

// Rate limiting : max 5 req/heure par IP
$ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
$ipHash = hash('sha256', explode(',', $ip)[0]);
$stmt = Database::pdo()->prepare(
    "SELECT COUNT(*) FROM article_clicks
     WHERE ip_hash = ? AND clicked_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
     AND utm_campaign = 'api-article-create'"
);
$stmt->execute([$ipHash]);
if ((int)$stmt->fetchColumn() >= 5) {
    respond(429, ['error' => 'Rate limit exceeded. Max 5/hour.']);
}

// Parse JSON body
$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data)) {
    respond(400, ['error' => 'Invalid JSON body.']);
}

// Validation des champs requis
$required = ['title', 'slug', 'description', 'keyword_target', 'content_html'];
foreach ($required as $field) {
    if (empty($data[$field])) {
        respond(400, ['error' => "Champ manquant : {$field}"]);
    }
}

// Sanitize / normaliser
$slug = Article::slugify((string)$data['slug']);
if ($slug === '' || Queue::isSlugTaken($slug)) {
    // Slug existe deja, on ajoute un suffixe
    $base = $slug ?: Article::slugify((string)$data['title']);
    $i = 2;
    $slug = $base . '-' . $i;
    while (Queue::isSlugTaken($slug)) {
        $i++;
        $slug = $base . '-' . $i;
    }
}

// Check duplication par mot-cle (anti-cannibalisation forte)
if (Queue::isKeywordTreated($data['keyword_target'])) {
    respond(409, [
        'error' => 'Ce mot-cle a deja un article.',
        'keyword' => $data['keyword_target'],
    ]);
}

// Quality check : longueur minimum
$contentText = strip_tags((string)$data['content_html']);
$wordCount = str_word_count($contentText);
if ($wordCount < 600) {
    respond(422, [
        'error' => 'Article trop court.',
        'word_count' => $wordCount,
        'min_required' => 600,
    ]);
}

// Preparer les donnees article
$articleData = [
    'slug'           => $slug,
    'title'          => trim((string)$data['title']),
    'description'    => substr(trim((string)$data['description']), 0, 500),
    'keyword_target' => trim((string)$data['keyword_target']),
    'cluster'        => in_array($data['cluster'] ?? '', ['comparatif', 'guide', 'test', 'saison', 'usage', 'diy', 'science', 'sante', 'faq', 'general'], true) ? $data['cluster'] : 'general',
    'persona'        => in_array($data['persona'] ?? '', ['tous', 'particulier', 'jardinier', 'pro', 'restaurateur', 'parent', 'eco'], true) ? $data['persona'] : 'tous',
    'content_html'   => (string)$data['content_html'],
    'featured_image' => null,
    'reading_time'   => max(1, min(60, (int)($data['reading_time'] ?? max(3, (int)($wordCount / 200))))),
    'status'         => 'published',
    'publish_at'     => date('Y-m-d H:i:s'),
    'author_id'      => null,
];

try {
    $articleId = Article::create($articleData);

    // Log la creation (pour le rate limit et audit)
    Database::pdo()->prepare(
        "INSERT INTO article_clicks (article_id, ip_hash, user_agent, referer, utm_campaign)
         VALUES (?, ?, ?, ?, 'api-article-create')"
    )->execute([
        $articleId,
        $ipHash,
        substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500),
        substr($_SERVER['HTTP_REFERER'] ?? '', 0, 500),
    ]);

    // Marker la queue comme "done" si queue_id fourni
    if (!empty($data['queue_id'])) {
        Queue::markDone((int)$data['queue_id'], $articleId);
    }

    respond(201, [
        'success'     => true,
        'article_id'  => $articleId,
        'slug'        => $slug,
        'url'         => 'https://cafetiereagrain.fr/blog/' . $slug,
        'word_count'  => $wordCount,
    ]);
} catch (\Throwable $e) {
    error_log('API article-create error: ' . $e->getMessage());
    if (!empty($data['queue_id'])) {
        Queue::markFailed((int)$data['queue_id'], $e->getMessage());
    }
    respond(500, ['error' =