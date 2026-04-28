<?php
// Liste des articles

declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';
require __DIR__ . '/_layout.php';

use App\Core\Article;

$articles = Article::listAll(200);

admin_header('Articles');
?>

<div class="flex items-center justify-between mb-8">
    <div>
        <h1 class="text-3xl font-black mb-1">Articles</h1>
        <p class="text-slate-500"><?= count($articles) ?> article<?= count($articles) > 1 ? 's' : '' ?></p>
    </div>
    <a href="/admin/article-edit.php" class="bg-gradient-to-r from-indigo-600 to-pink-500 text-white font-semibold px-5 py-2.5 rounded-xl hover:shadow-lg hover:shadow-indigo-500/30 transition">+ Nouvel article</a>
</div>

<?php if (isset($_GET['saved'])): ?>
    <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-4 mb-4 text-sm text-emerald-700">✅ Article enregistré</div>
<?php endif; ?>
<?php if (isset($_GET['deleted'])): ?>
    <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-4 mb-4 text-sm text-emerald-700">✅ Article supprimé</div>
<?php endif; ?>

<div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
    <?php if (empty($articles)): ?>
        <div class="text-center py-16 text-slate-400">
            <div class="text-5xl mb-3">📝</div>
            <p class="mb-6">Aucun article</p>
            <a href="/admin/article-edit.php" class="inline-block bg-indigo-600 text-white text-sm font-semibold px-4 py-2 rounded-lg">Créer le premier article</a>
        </div>
    <?php else: ?>
        <table class="w-full">
            <thead class="bg-slate-50 border-b border-slate-200">
                <tr>
                    <th class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500 px-6 py-3">Titre</th>
                    <th class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500 px-6 py-3">Cluster</th>
                    <th class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500 px-6 py-3">Statut</th>
                    <th class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500 px-6 py-3">Vues</th>
                    <th class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500 px-6 py-3">Date</th>
                    <th class="px-6 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php foreach ($articles as $a): ?>
                    <tr class="hover:bg-slate-50/50">
                        <td class="px-6 py-4">
                            <div class="font-medium text-slate-900"><?= htmlspecialchars($a->title, ENT_QUOTES, 'UTF-8') ?></div>
                            <div class="text-xs text-slate-400 font-mono">/<?= htmlspecialchars($a->slug, ENT_QUOTES, 'UTF-8') ?></div>
                        </td>
                        <td class="px-6 py-4 text-sm text-slate-600"><?= htmlspecialchars($a->cluster, ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="px-6 py-4">
                            <span class="inline-block px-2 py-0.5 rounded text-[10px] font-semibold uppercase <?php
                                echo match($a->status) {
                                    'published' => 'bg-emerald-100 text-emerald-700',
                                    'draft' => 'bg-amber-100 text-amber-700',
                                    'scheduled' => 'bg-blue-100 text-blue-700',
                                    default => 'bg-slate-100 text-slate-700'
                                };
                            ?>">
                                <?= htmlspecialchars($a->status, ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-slate-600"><?= $a->viewsCount ?></td>
                        <td class="px-6 py-4 text-sm text-slate-500"><?= htmlspecialchars(substr($a->createdAt, 0, 10), ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="px-6 py-4 text-right">
                            <a href="/admin/article-edit.php?id=<?= $a->id ?>" class="text-indigo-600 hover:underline text-sm font-medium mr-3">Éditer</a>
                            <?php if ($a->status === 'published'): ?>
                                <a href="/blog/<?= htmlspecialchars($a->slug, ENT_QUOTES, 'UTF-8') ?>" target="_blank" class="text-slate-400 hover:text-slate-700 text-sm">Voir</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php admin_footer(); ?>
