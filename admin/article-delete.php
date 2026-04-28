<?php
// POST handler : supprime un article

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

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    header('Location: /admin/articles.php');
    exit;
}

try {
    Article::delete($id);
    header('Location: /admin/articles.php?deleted=1');
    exit;
} catch (Throwable $e) {
    error_log('Article delete error: ' . $e->getMessage());
    http_response_code(500);
    exit('Erreur suppression.');
}
