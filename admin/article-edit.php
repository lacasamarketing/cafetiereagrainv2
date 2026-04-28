<?php
// Editeur d'article (creation + edition)

declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';
require __DIR__ . '/_layout.php';

use App\Core\Article;
use App\Core\CSRF;

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$article = $id > 0 ? Article::findById($id) : null;

$isNew = $article === null;

admin_header($isNew ? 'Nouvel article' : 'Éditer : ' . $article->title);
?>

<div class="flex items-center justify-between mb-6">
    <h1 class="text-3xl font-black"><?= $isNew ? 'Nouvel article' : 'Éditer article' ?></h1>
    <?php if (!$isNew): ?>
        <form action="/admin/article-delete.php" method="POST" onsubmit="return confirm('Supprimer cet article ? Cette action est irréversible.');">
            <?= CSRF::field() ?>
            <input type="hidden" name="id" value="<?= $article->id ?>">
            <button type="submit" class="text-sm text-red-600 hover:text-red-800 font-medium">🗑️ Supprimer</button>
        </form>
    <?php endif; ?>
</div>

<form action="/admin/article-save.php" method="POST" class="space-y-6">
    <?= CSRF::field() ?>
    <?php if (!$isNew): ?>
        <input type="hidden" name="id" value="<?= $article->id ?>">
    <?php endif; ?>

    <!-- TITRE + SLUG -->
    <div class="bg-white rounded-2xl border border-slate-200 p-6 space-y-4">
        <div>
            <label class="block text-sm font-semibold mb-1">Titre <span class="text-red-500">*</span></label>
            <input type="text" name="title" required id="titleInput"
                   value="<?= htmlspecialchars($article->title ?? '', ENT_QUOTES, 'UTF-8') ?>"
                   class="w-full px-4 py-2.5 rounded-lg border border-slate-300 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 outline-none text-lg font-semibold">
        </div>
        <div>
            <label class="block text-sm font-semibold mb-1">Slug URL <span class="text-red-500">*</span></label>
            <div class="flex items-center gap-2">
                <span class="text-slate-400 text-sm">/blog/</span>
                <input type="text" name="slug" required id="slugInput" pattern="[a-z0-9-]+"
                       value="<?= htmlspecialchars($article->slug ?? '', ENT_QUOTES, 'UTF-8') ?>"
                       class="flex-1 px-4 py-2.5 rounded-lg border border-slate-300 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 outline-none font-mono text-sm">
            </div>
        </div>
        <div>
            <label class="block text-sm font-semibold mb-1">Meta description <span class="text-red-500">*</span></label>
            <textarea name="description" required maxlength="500" rows="2"
                      class="w-full px-4 py-2.5 rounded-lg border border-slate-300 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 outline-none"><?= htmlspecialchars($article->description ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
            <p class="text-xs text-slate-400 mt-1">150-160 caractères idéal pour SEO</p>
        </div>
    </div>

    <!-- SEO / CLUSTER -->
    <div class="bg-white rounded-2xl border border-slate-200 p-6 space-y-4">
        <h3 class="font-bold">Ciblage SEO</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-semibold mb-1">Mot-clé cible <span class="text-red-500">*</span></label>
                <input type="text" name="keyword_target" required
                       value="<?= htmlspecialchars($article->keywordTarget ?? '', ENT_QUOTES, 'UTF-8') ?>"
                       class="w-full px-4 py-2.5 rounded-lg border border-slate-300 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200">
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">Cluster</label>
                <select name="cluster" class="w-full px-4 py-2.5 rounded-lg border border-slate-300 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200">
                    <?php
                    $clusters = ['use-case' => 'Cas d\'usage', 'comparatif' => 'Comparatif', 'technique' => 'Technique SEO', 'tuto' => 'Tutoriel', 'general' => 'Général'];
                    $currentCluster = $article->cluster ?? 'general';
                    foreach ($clusters as $k => $label): ?>
                        <option value="<?= $k ?>" <?= $currentCluster === $k ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">Persona cible</label>
                <select name="persona" class="w-full px-4 py-2.5 rounded-lg border border-slate-300 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200">
                    <?php
                    $personas = ['tous' => 'Tous', 'ecommerce' => 'E-commerçant', 'affiliation' => 'Affilié', 'entrepreneur' => 'Entrepreneur', 'agence' => 'Agence SEO'];
                    $currentPersona = $article->persona ?? 'tous';
                    foreach ($personas as $k => $label): ?>
                        <option value="<?= $k ?>" <?= $currentPersona === $k ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-semibold mb-1">Image mise en avant (URL)</label>
                <input type="url" name="featured_image"
                       value="<?= htmlspecialchars($article->featuredImage ?? '', ENT_QUOTES, 'UTF-8') ?>"
                       placeholder="/assets/img/articles/mon-article.webp"
                       class="w-full px-4 py-2.5 rounded-lg border border-slate-300 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200">
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">Temps de lecture (min)</label>
                <input type="number" name="reading_time" min="1" max="60"
                       value="<?= (int)($article->readingTime ?? 5) ?>"
                       class="w-full px-4 py-2.5 rounded-lg border border-slate-300 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200">
            </div>
        </div>
    </div>

    <!-- CONTENU HTML -->
    <div class="bg-white rounded-2xl border border-slate-200 p-6">
        <label class="block text-sm font-semibold mb-1">Contenu HTML <span class="text-red-500">*</span></label>
        <p class="text-xs text-slate-500 mb-2">Colle ici le HTML généré par WiseWand (ou écrit en HTML). Balises autorisées : h2-h6, p, ul, ol, li, strong, em, a, blockquote, img, table.</p>
        <textarea name="content_html" required rows="20"
                  class="w-full px-4 py-2.5 rounded-lg border border-slate-300 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 outline-none font-mono text-sm"><?= htmlspecialchars($article->contentHtml ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
    </div>

    <!-- STATUT -->
    <div class="bg-white rounded-2xl border border-slate-200 p-6 space-y-4">
        <h3 class="font-bold">Publication</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-semibold mb-1">Statut</label>
                <select name="status" class="w-full px-4 py-2.5 rounded-lg border border-slate-300 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200">
                    <?php
                    $statuses = ['draft' => 'Brouillon', 'published' => 'Publié', 'scheduled' => 'Programmé'];
                    $currentStatus = $article->status ?? 'draft';
                    foreach ($statuses as $k => $label): ?>
                        <option value="<?= $k ?>" <?= $currentStatus === $k ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">Date de publication</label>
                <input type="datetime-local" name="publish_at"
                       value="<?= htmlspecialchars(str_replace(' ', 'T', substr($article->publishAt ?? '', 0, 16)), ENT_QUOTES, 'UTF-8') ?>"
                       class="w-full px-4 py-2.5 rounded-lg border border-slate-300 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200">
                <p class="text-xs text-slate-400 mt-1">Laisse vide pour publier immédiatement</p>
            </div>
        </div>
    </div>

    <!-- ACTIONS -->
    <div class="flex items-center gap-3 sticky bottom-6 bg-white rounded-2xl border border-slate-200 p-4 shadow-xl">
        <button type="submit" class="bg-gradient-to-r from-indigo-600 to-pink-500 text-white font-semibold px-6 py-3 rounded-xl hover:shadow-lg hover:shadow-indigo-500/30 transition">
            💾 Enregistrer
        </button>
        <a href="/admin/articles.php" class="text-slate-600 font-medium px-4 py-3 hover:text-slate-900">Annuler</a>
    </div>
</form>

<script>
// Auto-slug from title for new articles
(function() {
    const titleInput = document.getElementById('titleInput');
    const slugInput = document.getElementById('slugInput');
    if (!titleInput || !slugInput) return;
    <?php if ($isNew): ?>
    let slugEdited = false;
    slugInput.addEventListener('input', () => { slugEdited = true; });
    titleInput.addEventListener('input', () => {
        if (slugEdited) return;
        const s = titleInput.value.toLowerCase()
            .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '')
            .substring(0, 190);
        slugInput.value = s;
    });
    <?php endif; ?>
})();
</script>

<?php admin_footer(); ?>
