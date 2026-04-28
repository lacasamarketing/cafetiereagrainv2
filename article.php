<?php
// Article single cafetiereagrain.fr
// URL: /blog/{slug}

declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

use App\Core\Article;
use App\Core\Database;
use App\Core\Layout;

$cfg = Layout::loadConfig();

// Fix OVH FastCGI : recup slug depuis 3 sources possibles (GET, PATH_INFO, REQUEST_URI)
$slug = isset($_GET['slug']) ? (string)$_GET['slug'] : '';
if ($slug === '' && !empty($_SERVER['PATH_INFO'])) {
    $slug = trim((string)$_SERVER['PATH_INFO'], '/');
}
if ($slug === '' && !empty($_SERVER['REQUEST_URI'])) {
    $_path = parse_url((string)$_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '';
    if (preg_match('#^/blog/([a-z0-9\-]+)/?$#i', $_path, $_m)) {
        $slug = $_m[1];
    }
}
$slug = preg_replace('/[^a-z0-9\-]/', '', strtolower($slug));

if ($slug === '') {
    http_response_code(404);
    echo '404';
    exit;
}

try {
    $pdo = Database::pdo();
    $stmt = $pdo->prepare("SELECT * FROM articles WHERE slug = ? AND status = 'published' LIMIT 1");
    $stmt->execute([$slug]);
    $article = $stmt->fetch();
} catch (Throwable $e) {
    $article = null;
}

if (!$article) {
    http_response_code(404);
    $pageTitle = 'Article introuvable · Cafetière à grain';
    $pageDescription = '';
    require __DIR__ . '/partials/header.php';
    echo '<main class="article-wrap"><div class="container"><a href="/blog" class="article-back">← Tous les articles</a><h1 class="article-h1">404 · Article introuvable.</h1><p class="text-muted">Le lien que tu as suivi est cassé. Tu peux <a href="/blog" class="text-uv">consulter tous les articles</a>.</p></div></main>';
    require __DIR__ . '/partials/footer.php';
    exit;
}

// Increment views (best-effort)
try {
    $pdo->prepare('UPDATE articles SET views_count = views_count + 1 WHERE id = ?')->execute([(int)$article['id']]);
} catch (Throwable $e) {}

// SEO : title incorpore "cafetière à grains" si pas déjà présent
$titleHasKeyword = stripos($article['title'], 'cafetière à grain') !== false || stripos($article['title'], 'piege a moustique') !== false;
$pageTitle = $article['title'] . ($titleHasKeyword ? '' : ' — Cafetière à grains') . ' · cafetiereagrain.fr';
$pageDescription = $article['description'] ?: ('Analyse et recommandation : ' . $article['title']);
$pageDescription = mb_substr($pageDescription, 0, 158);
$canonical = ($cfg['base_url'] ?? '') . '/blog/' . $article['slug'];
$ogImage = ($cfg['base_url'] ?? '') . '/blog/' . $article['slug'] . '.svg';

// Maillage interne : 3 articles du même cluster + produits cités dans l'article
$relatedArticles = [];
$citedProducts = [];
try {
    $stmt = $pdo->prepare("SELECT slug, title, description, cluster, reading_time
                           FROM articles
                           WHERE status = 'published' AND cluster = ? AND id <> ?
                           ORDER BY publish_at DESC LIMIT 3");
    $stmt->execute([$article['cluster'], (int)$article['id']]);
    $relatedArticles = $stmt->fetchAll();

    // Produits cités via /go/{slug} dans le HTML
    if (preg_match_all('#/go/([a-z0-9\-]+)#i', (string)$article['content_html'], $matches)) {
        $slugs = array_unique($matches[1]);
        if (!empty($slugs)) {
            $place = implode(',', array_fill(0, count($slugs), '?'));
            $stmt3 = $pdo->prepare("SELECT slug, name, brand, technology, surface_m2, price_eur, rating, badge, verdict, target_use, image_url, gallery_images
                                    FROM products WHERE status = 'published' AND slug IN ($place) ORDER BY rank_global ASC LIMIT 3");
            $stmt3->execute($slugs);
            $citedProducts = $stmt3->fetchAll();
        }
    }
} catch (Throwable $e) {}

require __DIR__ . '/partials/header.php';
?>

<main class="article-wrap">
    <div class="container" style="max-width: 880px;">
        <a href="/blog" class="article-back">← Tous les articles</a>

        <?php if (!empty($article['cluster'])): ?>
            <div class="article-cluster"><?= Layout::escape(strtoupper($article['cluster'])) ?></div>
        <?php endif; ?>

        <h1 class="article-h1"><?= Layout::escape($article['title']) ?></h1>

        <?php if (!empty($article['description'])): ?>
            <p class="article-lede"><?= Layout::escape($article['description']) ?></p>
        <?php endif; ?>

        <div class="article-meta">
            <?= !empty($article['publish_at']) ? date('d.m.Y', strtotime((string)$article['publish_at'])) : '' ?>
            <?php if (!empty($article['reading_time'])): ?>
                · <?= (int)$article['reading_time'] ?> min de lecture
            <?php endif; ?>
        </div>

        <!-- GALERIES PHOTOS PRODUITS CITÉS (Rainforest API) — en haut comme une fiche produit -->
        <?php
        $galleries = [];
        foreach ($citedProducts as $cpg) {
            $imgs = !empty($cpg['gallery_images']) ? json_decode((string)$cpg['gallery_images'], true) : [];
            if (is_array($imgs) && count($imgs) >= 2) {
                $galleries[] = [
                    'slug' => $cpg['slug'],
                    'name' => $cpg['name'],
                    'brand' => $cpg['brand'] ?? '',
                    'images' => array_slice($imgs, 0, 6),
                ];
            }
        }
        ?>
        <?php if (!empty($galleries)): ?>
        <section class="article-galleries article-galleries--top">
            <?php foreach ($galleries as $g): ?>
                <div class="article-gallery">
                    <div class="article-gallery-head">
                        <div class="article-gallery-eyebrow">Photos produit · Amazon</div>
                        <h3 class="article-gallery-title"><?= Layout::escape($g['name']) ?></h3>
                    </div>
                    <div class="article-gallery-grid">
                        <?php foreach ($g['images'] as $i => $imgUrl): ?>
                            <a href="/go/<?= Layout::escape($g['slug']) ?>?campaign=article-gallery-<?= Layout::escape($article['slug']) ?>"
                               rel="noopener sponsored"
                               class="article-gallery-item"
                               aria-label="Voir <?= Layout::escape($g['name']) ?> sur Amazon">
                                <img src="<?= Layout::escape((string)$imgUrl) ?>"
                                     alt="<?= Layout::escape($g['name']) ?> — vue <?= ($i + 1) ?>"
                                     loading="<?= $i < 3 ? 'eager' : 'lazy' ?>"
                                     decoding="async">
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </section>
        <style>
        .article-galleries { display: flex; flex-direction: column; gap: 2.5rem; }
        .article-galleries--top { margin: 1.5rem 0 2.5rem; padding-bottom: 2rem; border-bottom: 1px solid var(--line, rgba(74,44,20,.1)); }
        .article-gallery-head { margin-bottom: 1rem; }
        .article-gallery-eyebrow { font-family: 'Inter', sans-serif; font-size: 11px; letter-spacing: .15em; text-transform: uppercase; color: var(--forest, #1f5742); font-weight: 500; }
        .article-gallery-title { font-family: 'Fraunces', serif; font-size: clamp(20px, 2.4vw, 26px); color: var(--forest, #1f5742); margin: 6px 0 0; font-weight: 500; line-height: 1.2; }
        .article-gallery-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; }
        .article-gallery-item { display: block; aspect-ratio: 1 / 1; background: #f5f8f0; border-radius: 12px; overflow: hidden; transition: transform .25s, box-shadow .25s; position: relative; }
        .article-gallery-item:hover { transform: translateY(-2px); box-shadow: 0 12px 28px rgba(74,44,20,.12); }
        .article-gallery-item img { width: 100%; height: 100%; object-fit: contain; padding: 14px; box-sizing: border-box; transition: transform .35s; }
        .article-gallery-item:hover img { transform: scale(1.04); }
        @media (max-width: 720px) {
            .article-gallery-grid { grid-template-columns: repeat(2, 1fr); gap: 8px; }
            .article-gallery-item img { padding: 10px; }
        }
        </style>
        <?php endif; ?>

        <div class="prose">
            <?= $article['content_html'] /* déjà sanitizé en upstream */ ?>
        </div>

        <div class="affiliation-disclaimer">
            Cet article peut contenir des liens d'affiliation Amazon. Si tu achètes via ces liens,
            nous touchons une commission sans surcoût pour toi. Notre sélection s'appuie uniquement sur
            l'analyse des retours utilisateurs et des spécifications constructeurs.
        </div>

        <!-- MAILLAGE : produits cités dans l'article -->
        <?php if (!empty($citedProducts)): ?>
        <section style="margin-top: 4rem; padding-top: 3rem; border-top: 1px solid var(--line);">
            <div class="section__eyebrow">Cafetières à grain cités dans cet article</div>
            <h2 class="section__title" style="font-size: clamp(1.5rem, 3.2vw, 2.2rem); margin-bottom: 2rem;">Les modèles évoqués.</h2>
            <div class="product-grid">
                <?php foreach ($citedProducts as $cp): ?>
                    <article class="product-card">
                        <div class="product-card__media" style="aspect-ratio:16/9;">
                            <?= Layout::productImage($cp, 'card') ?>
                        </div>
                        <div class="product-card__body">
                            <div class="product-card__cat"><?= Layout::escape(strtoupper((string)$cp['technology'])) ?></div>
                            <h3 class="product-card__name"><?= Layout::escape((string)$cp['name']) ?></h3>
                            <p class="product-card__verdict">"<?= Layout::escape((string)$cp['verdict']) ?>"</p>
                        </div>
                        <div class="product-card__cta">
                            <a class="btn-amazon" href="/go/<?= Layout::escape($cp['slug']) ?>?campaign=article-cited-<?= Layout::escape((string)$article['slug']) ?>" rel="noopener sponsored">Voir Amazon</a>
                            <a class="btn-detail" href="/tests/<?= Layout::escape((string)$cp['slug']) ?>">Test</a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <!-- MAILLAGE : articles connexes du même cluster -->
        <?php if (!empty($relatedArticles)): ?>
        <section style="margin-top: 3.5rem; padding-top: 3rem; border-top: 1px solid var(--line); padding-bottom: 2rem;">
            <div class="section__eyebrow">À lire aussi sur le cafetière à grains</div>
            <h2 class="section__title" style="font-size: clamp(1.5rem, 3.2vw, 2.2rem); margin-bottom: 2rem;">Articles similaires.</h2>
            <div class="guides">
                <?php foreach ($relatedArticles as $ra): ?>
                    <a class="guide-card" href="/blog/<?= Layout::escape((string)$ra['slug']) ?>">
                        <div class="cat"><?= Layout::escape(strtoupper((string)$ra['cluster'])) ?></div>
                        <h3 class="h"><?= Layout::escape((string)$ra['title']) ?></h3>
                        <p class="d"><?= Layout::escape((string)$ra['description']) ?></p>
                        <div class="arr">LIRE L'ARTICLE →</div>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>
    </div>
</main>

<?php
// JSON-LD Article + BreadcrumbList
$baseUrl = rtrim($cfg['base_url'] ?? 'https://cafetiereagrain.fr', '/');
$clusterLabel = ucfirst((string)$article['cluster']);
$ld = [
    '@context' => 'https://schema.org',
    '@graph' => [
        [
            '@type' => 'Article',
            'headline' => $article['title'],
            'description' => $article['description'],
            'datePublished' => $article['publish_at'] ?: $article['created_at'],
            'dateModified'  => $article['updated_at'] ?: ($article['publish_at'] ?: $article['created_at']),
            'mainEntityOfPage' => $canonical,
            'image' => $ogImage,
            'author'    => ['@type' => 'Organization', '@id' => $baseUrl . '/#organization'],
            'publisher' => ['@id' => $baseUrl . '/#organization'],
            'keywords' => $article['keyword_target'] ?? '',
            'inLanguage' => 'fr-FR',
        ],
        [
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => 'Accueil', 'item' => $baseUrl . '/'],
                ['@type' => 'ListItem', 'position' => 2, 'name' => 'Blog', 'item' => $baseUrl . '/blog'],
                ['@type' => 'ListItem', 'position' => 3, 'name' => $clusterLabel, 'item' => $baseUrl . '/blog?cluster=' . $article['cluster']],
                ['@type' => 'ListItem', 'position' => 4, 'name' => $article['title'], 'item' => $canonical],
            ],
        ],
    ],
];
echo "\n<script type=\"application/ld+json\">" . json_encode($ld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "</script>\n";

// JSON-LD FAQPage si l'article contient un H2 "Questions fréquentes" suivi de H3
if (preg_match('/<h2[^>]*>\s*Questions\s+fr[eé]quentes/iu', (string)$article['content_html'])) {
    if (preg_match_all('#<h3[^>]*>(.+?)</h3>\s*<p[^>]*>(.+?)</p>#iu', (string)$article['content_html'], $faqMatches, PREG_SET_ORDER)) {
        $faqEntries = [];
        foreach (array_slice($faqMatches, 0, 6) as $m) {
            $faqEntries[] = [
                '@type' => 'Question',
                'name' => trim(strip_tags($m[1])),
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => trim(strip_tags($m[2]))],
            ];
        }
        if (!empty($faqEntries)) {
            $faqLd = ['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => $faqEntries];
            echo "\n<script type=\"application/ld+json\">" . json_encode($faqLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "</script>\n";
        }
    }
}

// Quiz JS si l'article contient un data-quiz
if (str_contains((string)$article['content_html'], 'data-quiz')) {
    echo "\n<script src=\"/assets/js/quiz.js\" defer></script>\n";
}
?>

<?php require __DIR__ . '/partials/footer.php';
