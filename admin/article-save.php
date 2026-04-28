<?php
// POST handler : enregistre un article (creation ou update)

declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

use App\Core\Auth;
use App\Core\CSRF;
use App\Core\Article;

Auth::requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /admin/articles.php');
    exit;
}

if (!CSRF::verify($_POST['_csrf'] ?? null)) {
    http_response_code(403);
    exit('Token CSRF invalide.');
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$user = Auth::currentUser();

// Sanitize + validate
$data = [
    'slug'           => Article::slugify((string)($_POST['slug'] ?? '')),
    'title'          => trim((string)($_POST['title'] ?? '')),
    'description'    => trim((string)($_POST['description'] ?? '')),
    'keyword_target' => trim((string)($_POST['keyword_target'] ?? '')),
    'cluster'        => in_array($_POST['cluster'] ?? '', ['use-case', 'comparatif', 'technique', 'tuto', 'general'], true) ? $_POST['cluster'] : 'general',
    'persona'        => in_array($_POST['persona'] ?? '', ['tous', 'ecommerce', 'affiliation', 'entrepreneur', 'agence'], true) ? $_POST['persona'] : 'tous',
    'content_html'   => (string)($_POST['content_html'] ?? ''),
    'featured_image' => trim((string)($_POST['featured_image'] ?? '')) ?: null,
    'reading_time'   => max(1, min(60, (int)($_POST['reading_time'] ?? 5))),
    'status'         => in_array($_POST['status'] ?? '', ['draft', 'published', 'scheduled'], true) ? $_POST['status'] : 'draft',
    'publish_at'     => !empty($_POST['publish_at']) ? str_replace('T', ' ', $_POST['publish_at']) . ':00' : null,
    'author_id'      => $user['id'] ?? null,
];

// Validation
if ($data['slug'] === '' || $data['title'] === '' || $data['description'] === '' || $data['keyword_target'] === '' || $data['content_html'] === '') {
    http_response_code(400);
    exit('Champs obligatoires manquants.');
}

try {
    if ($id > 0) {
        Article::update($id, $data);
        $savedId = $id;
    } else {
        $savedId = Article::create($data);
    }
    header('Location: /admin/articles.php?saved=1');
    exit;
} catch (Throwable $e) {
    error_log('Article save error: ' . $e->getMessage());
    http_response_code(500);
    exit('Erreur lors de l\'enregistrement: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
}
