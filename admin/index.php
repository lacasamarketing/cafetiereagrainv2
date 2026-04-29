<?php
declare(strict_types=1);
require __DIR__ . '/../app/bootstrap.php';
require __DIR__ . '/_layout.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\Queue;

Auth::requireLogin();

$pdo = Database::pdo();

// Stats
$stats = [
    'articles_published' => (int)$pdo->query("SELECT COUNT(*) FROM articles WHERE status='published'")->fetchColumn(),
    'products_published' => (int)$pdo->query("SELECT COUNT(*) FROM products WHERE status='published'")->fetchColumn(),
];
try { $qs = Queue::stats(); } catch(\Throwable $e){ $qs = ['pending'=>0,'in_progress'=>0,'done'=>0,'failed'=>0]; }

// 5 derniers articles
$recent = $pdo->query("SELECT id, slug, title, publish_at FROM articles WHERE status='published' ORDER BY publish_at DESC LIMIT 5")->fetchAll();

admin_header('Tableau de bord');
?>
<h1 class="text-3xl font-black mb-8">Tableau de bord</h1>

<div class="grid grid-cols-4 gap-4 mb-8">
    <div class="bg-white border rounded-xl p-5"><div class="text-xs uppercase tracking-wide text-slate-500 font-semibold">Articles</div><div class="text-3xl font-black mt-2"><?= $stats['articles_published'] ?></div></div>
    <div class="bg-white border rounded-xl p-5"><div class="text-xs uppercase tracking-wide text-slate-500 font-semibold">Produits</div><div class="text-3xl font-black mt-2"><?= $stats['products_published'] ?></div></div>
    <div class="bg-amber-50 border border-amber-200 rounded-xl p-5"><div class="text-xs uppercase tracking-wide text-amber-700 font-semibold">Queue pending</div><div class="text-3xl font-black mt-2 text-amber-900"><?= $qs['pending'] ?? 0 ?></div></div>
    <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-5"><div class="text-xs uppercase tracking-wide text-emerald-700 font-semibold">Queue done</div><div class="text-3xl font-black mt-2 text-emerald-900"><?= $qs['done'] ?? 0 ?></div></div>
</div>

<div class="bg-white border rounded-xl p-6">
    <div class="flex items-center justify-between mb-4">
        <h2 class="font-black text-lg">Derniers articles publies</h2>
        <a href="/admin/articles.php" class="text-sm text-amber-700 hover:underline">Voir tout</a>
    </div>
    <?php if (empty($recent)): ?>
        <p class="text-sm text-slate-500">Aucun article pour le moment.</p>
    <?php else: ?>
        <div class="divide-y" style="border-color: var(--pam-line);">
            <?php foreach ($recent as $a): ?>
                <div class="py-3 flex items-center justify-between">
                    <div class="min-w-0 flex-1">
                        <div class="font-medium truncate"><?= htmlspecialchars($a['title'], ENT_QUOTES, 'UTF-8') ?></div>
                        <div class="text-xs text-slate-500"><?= htmlspecialchars($a['publish_at'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                    <a href="/<?= htmlspecialchars($a['slug'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" class="text-xs text-amber-700 hover:underline ml-4">Voir</a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php admin_footer(); ?>
