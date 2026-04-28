<?php
// Dashboard admin cafetiereagrain.fr

declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';
require __DIR__ . '/_layout.php';

use App\Core\Database;
use App\Core\Article;

$pdo = Database::pdo();

// Stats articles
$totalArticles = (int)$pdo->query('SELECT COUNT(*) FROM articles')->fetchColumn();
$publishedArticles = Article::countPublished();
$draftArticles = (int)$pdo->query("SELECT COUNT(*) FROM articles WHERE status = 'draft'")->fetchColumn();

// Stats clics affiliation Amazon (via /go/)
$totalClicks = (int)$pdo->query('SELECT COUNT(*) FROM article_clicks')->fetchColumn();
$todayClicks = (int)$pdo->query("SELECT COUNT(*) FROM article_clicks WHERE DATE(clicked_at) = CURDATE()")->fetchColumn();
$weekClicks = (int)$pdo->query("SELECT COUNT(*) FROM article_clicks WHERE clicked_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn();

// Stats produits
$totalProducts = 0;
try {
    $totalProducts = (int)$pdo->query("SELECT COUNT(*) FROM products WHERE status = 'published'")->fetchColumn();
} catch (Throwable $e) {}

// Stats queue
$queuePending = 0;
try {
    $queuePending = (int)$pdo->query("SELECT COUNT(*) FROM article_queue WHERE status = 'pending'")->fetchColumn();
} catch (Throwable $e) {}

// Recent articles
$recent = $pdo->query('SELECT id, title, status, created_at, slug, cluster FROM articles ORDER BY created_at DESC LIMIT 6')->fetchAll();

// Top produits par clics affiliation (sur 30 jours)
$topProducts = [];
try {
    $topProducts = $pdo->query(
        "SELECT utm_campaign, COUNT(*) AS clicks
         FROM article_clicks
         WHERE clicked_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
           AND utm_medium = 'affiliate'
         GROUP BY utm_campaign
         ORDER BY clicks DESC LIMIT 5"
    )->fetchAll();
} catch (Throwable $e) {}

admin_header('Tableau de bord');
?>

<div class="mb-8">
    <h1 class="display" style="font-size: 2.25rem; font-weight: 500; letter-spacing: -0.02em;">Tableau de bord</h1>
    <p style="color: var(--pam-muted); margin-top: 0.25rem;">Vue d'ensemble de cafetiereagrain.fr</p>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-10">
    <div class="pam-card p-6">
        <div class="text-xs uppercase font-semibold tracking-wide mb-2" style="color: var(--pam-muted);">Articles publiés</div>
        <div class="text-3xl font-black pam-num-published"><?= $publishedArticles ?></div>
        <div class="text-xs mt-1" style="color: var(--pam-muted);">sur <?= $totalArticles ?> total</div>
    </div>
    <div class="pam-card p-6">
        <div class="text-xs uppercase font-semibold tracking-wide mb-2" style="color: var(--pam-muted);">Brouillons</div>
        <div class="text-3xl font-black pam-num-drafts"><?= $draftArticles ?></div>
        <div class="text-xs mt-1" style="color: var(--pam-muted);">à éditer / publier</div>
    </div>
    <div class="pam-card p-6">
        <div class="text-xs uppercase font-semibold tracking-wide mb-2" style="color: var(--pam-muted);">Clics Amazon (auj.)</div>
        <div class="text-3xl font-black pam-num-clicks"><?= $todayClicks ?></div>
        <div class="text-xs mt-1" style="color: var(--pam-muted);">7&nbsp;jours&nbsp;: <?= $weekClicks ?> · total&nbsp;: <?= $totalClicks ?></div>
    </div>
    <div class="pam-card p-6">
        <div class="text-xs uppercase font-semibold tracking-wide mb-2" style="color: var(--pam-muted);">Queue en attente</div>
        <div class="text-3xl font-black pam-num-total"><?= $queuePending ?></div>
        <div class="text-xs mt-1" style="color: var(--pam-muted);">topics à rédiger · <?= $totalProducts ?> produits actifs</div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-10">
    <!-- Articles récents -->
    <div class="pam-card p-6 lg:col-span-2">
        <div class="flex items-center justify-between mb-4">
            <h2 class="font-bold text-lg display" style="font-weight: 500;">Articles récents</h2>
            <a href="/admin/article-edit.php" class="pam-btn-primary">+ Nouvel article</a>
        </div>

        <?php if (empty($recent)): ?>
            <div class="text-center py-12" style="color: var(--pam-muted);">
                <p class="mb-4">Aucun article pour le moment</p>
                <a href="/admin/article-edit.php" class="pam-btn-primary">Créer le premier article</a>
            </div>
        <?php else: ?>
            <div class="divide-y" style="border-color: var(--pam-line);">
                <?php foreach ($recent as $a): ?>
                    <div class="py-3 flex items-center justify-between">
                        <div class="min-w-0 flex-1">
                            <div class="font-medium truncate"><?= htmlspecialchars($a['title'], ENT_QUOTES, 'UTF-8') ?></div>
                