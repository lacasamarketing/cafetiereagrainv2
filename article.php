<?php
// Article single cafetiereagrain.fr
// URL : /{slug} (URL racine pour preserver SEO du WP existant)

declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

use App\Core\Database;
use App\Core\Layout;

$cfg = Layout::loadConfig();
$slug = $_GET['slug'] ?? '';

if (!preg_match('/^[a-z0-9-]{1,190}$/', $slug)) {
    http_response_code(404);
    $pageTitle = 'Article introuvable';
    require __DIR__ . '/partials/header.php';
    echo '<main class="container" style="padding:6rem 2rem;text-align:center;"><h1 class="display">Article introuvable</h1><p><a href="/blog">Retourner au blog →</a></p></main>';
    require __DIR__ . '/partials/footer.php';
    exit;
}

$article = null;
$products_in_article = [];
$related_articles = [];

try {
    $pdo = Database::pdo();
    $stmt = $pdo->prepare("SELECT * FROM articles WHERE slug = :s AND status = 'published' LIMIT 1");
    $stmt->execute([':s' => $slug]);
    $article = $stmt->fetch();

    if ($article) {
        // Produits cites dans l article (via maillage interne)
        $stmt = $pdo->prepare(
            "SELECT p.asin, p.slug AS p_slug, p.name, p.brand, p.main_image_url, p.price_eur, p.rating, p.ratings_total,
                    p.summary_avis, p.type_cafetiere, p.bestsellers_rank, ap.position
             FROM products p
             INNER JOIN article_products ap ON ap.product_id = p.id
             WHERE ap.article_id = :id AND p.status = 'published'
             ORDER BY ap.position ASC LIMIT 6"
        );
        $stmt->execute([':id' => $article['id']]);
        $products_in_article = $stmt->fetchAll();

        // Articles similaires (meme cluster)
        $stmt = $pdo->prepare(
            "SELECT slug, title, description, reading_time, publish_at
             FROM articles WHERE cluster = :c AND id != :id AND status = 'published'
             ORDER BY publish_at DESC LIMIT 4"
        );
        $stmt->execute([':c' => $article['cluster'], ':id' => $article['id']]);
        $related_articles = $stmt->fetchAll();

        // Increment vue
        $pdo->prepare('UPDATE articles SET views_count = views_count + 1 WHERE id = ?')
            ->execute([$article['id']]);
    }
} catch (Throwable $e) {
    error_log('article.php DB error: ' . $e->getMessage());
}

if (!$article) {
    http_response_code(404);
    $pageTitle = 'Article introuvable';
    require __DIR__ . '/partials/header.php';
    echo '<main class="container" style="padding:6rem 2rem;text-align:center;"><h1 class="display">Article introuvable</h1><p>Cet article n\'existe pas ou plus. <a href="/blog">Retourner au blog →</a></p></main>';
    require __DIR__ . '/partials/footer.php';
    exit;
}

// Nettoyage du contenu HTML : supprime les <img> mortes pointant vers wp-content/uploads
// (les images Planethoster ne resolvent plus, on les remplace par les images Rainforest des produits)
$content = $article['content_html'];
$content = preg_replace('#<img[^>]*src=["\'][^"\']*wp-content/uploads/[^"\']*["\'][^>]*/?>#i', '', $content);
// Rewrite des liens Amazon directs avec notre tag affilie
$amazonTag = $cfg['affiliate']['amazon_tag'] ?? 'lacasamarke08-21';
$content = preg_replace_callback(
    '#href=["\']https://www\.amazon\.[a-z.]+/(?:[A-Za-z0-9_-]+/)?(?:dp|gp/product|product)/([A-Z0-9]{10})[^"\']*["\']#i',
    function($m) use ($amazonTag) {
        return 'href="https://www.amazon.fr/dp/' . $m[1] . '?tag=' . urlencode($amazonTag) . '" target="_blank" rel="noopener sponsored"';
    },
    $content
);

$pageTitle = $article['title'] . ' · Cafetière à grain';
$pageDescription = !empty($article['description']) ? $article['description'] : 'Comparatif et avis détaillé sur cafetiereagrain.fr.';
$canonical = ($cfg['base_url'] ?? 'https://cafetiereagrain.fr') . '/' . $article['slug'];
$ogImage = $article['featured_image'] ?? (!empty($products_in_article) ? $products_in_article[0]['main_image_url'] : '');

require __DIR__ . '/partials/header.php';
?>

<!-- JSON-LD Article -->
<script type="application/ld+json"><?= json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'Article',
    'headline' => $article['title'],
    'description' => $article['description'],
    'image' => $ogImage ?: null,
    'datePublished' => $article['publish_at'],
    'dateModified' => $article['updated_at'],
    'author' => ['@type' => 'Organization', 'name' => 'Cafetière à grain'],
    'publisher' => [
        '@type' => 'Organization',
        'name' => 'Cafetière à grain',
        'logo' => ['@type' => 'ImageObject', 'url' => ($cfg['base_url'] ?? '') . '/assets/img/logo.svg'],
    ],
    'mainEntityOfPage' => $canonical,
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?></script>

<main class="article-page">
    <div class="container article-layout">

        <article class="article">
            <nav class="breadcrumb">
                <a href="/">Accueil</a>
                <span class="breadcrumb__sep">›</span>
                <a href="/categorie/<?= Layout::escape($article['cluster']) ?>"><?= Layout::escape($article['cluster']) ?></a>
                <span class="breadcrumb__sep">›</span>
                <span class="breadcrumb__current"><?= Layout::escape(mb_substr($article['title'], 0, 60)) ?></span>
            </nav>

            <header class="article-header">
                <span class="article-header__cluster"><?= Layout::escape($article['cluster']) ?></span>
                <h1 class="display"><?= Layout::escape($article['title']) ?></h1>
                <?php if (!empty($article['description'])): ?>
                    <p class="article-header__lead"><?= Layout::escape($article['description']) ?></p>
                <?php endif; ?>
                <div class="article-header__meta">
                    <?php if (!empty($article['publish_at'])): ?>
                        <span>Publié le <?= date('j F Y', strtotime($article['publish_at'])) ?></span>
                    <?php endif; ?>
                    <span>·</span>
                    <span><?= (int)$article['reading_time'] ?> min de lecture</span>
                </div>
            </header>

            <!-- BLOC PRODUIT RECOMMANDÉ inline (si article = test produit) -->
            <?php if (!empty($products_in_article) && $products_in_article[0]['position'] == 1):
                $main_p = $products_in_article[0];
                $amazonUrl = 'https://www.amazon.fr/dp/' . urlencode($main_p['asin']) . '?tag=' . urlencode($amazonTag);
            ?>
                <aside class="article-product-hero">
                    <?php if (!empty($main_p['main_image_url'])): ?>
                        <div class="article-product-hero__img">
                            <img src="<?= Layout::escape($main_p['main_image_url']) ?>" alt="<?= Layout::escape($main_p['name']) ?>" loading="eager">
                        </div>
                    <?php endif; ?>
                    <div class="article-product-hero__info">
                        <div class="article-product-hero__brand"><?= Layout::escape($main_p['brand']) ?></div>
                        <div class="article-product-hero__name"><?= Layout::escape(mb_substr($main_p['name'], 0, 80)) ?></div>
                        <?php if (!empty($main_p['rating'])): ?>
                            <div class="article-product-hero__rating">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <span class="star<?= $i <= floor((float)$main_p['rating']) ? ' is-full' : '' ?>">★</span>
                                <?php endfor; ?>
                                <span><?= number_format((float)$main_p['rating'], 1) ?>/5 · <?= number_format((int)$main_p['ratings_total'], 0, ',', ' ') ?> avis</span>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($main_p['price_eur'])): ?>
                            <div class="article-product-hero__price"><?= number_format((float)$main_p['price_eur'], 0, ',', ' ') ?> €</div>
                            <div class="article-product-hero__price-meta">Prix Amazon mis à jour aujourd'hui</div>
                        <?php endif; ?>
                        <a href="<?= Layout::escape($amazonUrl) ?>" target="_blank" rel="noopener sponsored" class="btn btn--primary btn--xl">
                            Voir sur Amazon →
                        </a>
                        <a href="/cafetiere/<?= Layout::escape($main_p['asin']) ?>" class="article-product-hero__details">Voir la fiche complète</a>
                    </div>
                </aside>
            <?php endif; ?>

            <!-- CONTENU ARTICLE (HTML scraped from WP, nettoye) -->
            <div class="article-content">
                <?= $content ?>
            </div>

            <!-- SYNTHESE AVIS (si on a un produit principal et un summary) -->
            <?php if (!empty($products_in_article) && !empty($products_in_article[0]['summary_avis'])): ?>
                <aside class="article-summary-avis">
                    <h2>Ce que disent les acheteurs Amazon</h2>
                    <div class="article-summary-avis__quotes">
                        <?php foreach (explode(' | ', $products_in_article[0]['summary_avis']) as $quote): ?>
                            <blockquote>
                                <p>« <?= Layout::escape(mb_substr($quote, 0, 240)) ?>... »</p>
                            </blockquote>
                        <?php endforeach; ?>
                    </div>
                    <p class="article-summary-avis__cta">
                        <a href="/cafetiere/<?= Layout::escape($products_in_article[0]['asin']) ?>">Voir les avis complets et la fiche produit →</a>
                    </p>
                </aside>
            <?php endif; ?>

            <!-- PRODUITS ALTERNATIFS (si plusieurs produits dans l article) -->
            <?php if (count($products_in_article) > 1): ?>
                <aside class="article-alternatives">
                    <h2>Alternatives mentionnées dans cet article</h2>
                    <div class="article-alternatives__grid">
                        <?php foreach (array_slice($products_in_article, 1, 5) as $alt): ?>
                            <a class="alt-card" href="/cafetiere/<?= Layout::escape($alt['asin']) ?>">
                                <?php if (!empty($alt['main_image_url'])): ?>
                                    <img src="<?= Layout::escape($alt['main_image_url']) ?>" alt="" loading="lazy">
                                <?php endif; ?>
                                <div class="alt-card__brand"><?= Layout::escape($alt['brand']) ?></div>
                                <div class="alt-card__name"><?= Layout::escape(mb_substr($alt['name'], 0, 60)) ?></div>
                                <?php if (!empty($alt['price_eur'])): ?>
                                    <div class="alt-card__price"><?= number_format((float)$alt['price_eur'], 0, ',', ' ') ?> €</div>
                                <?php endif; ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </aside>
            <?php endif; ?>

            <!-- ARTICLES SIMILAIRES -->
            <?php if (!empty($related_articles)): ?>
                <aside class="article-related">
                    <h2>À lire aussi dans cette catégorie</h2>
                    <div class="related-list">
                        <?php foreach ($related_articles as $rel): ?>
                            <a class="related-list__item" href="/<?= Layout::escape($rel['slug']) ?>">
                                <h3><?= Layout::escape($rel['title']) ?></h3>
                                <?php if (!empty($rel['description'])): ?>
                                    <p><?= Layout::escape(mb_substr($rel['description'], 0, 120)) ?>…</p>
                                <?php endif; ?>
                                <span class="related-list__cta"><?= (int)$rel['reading_time'] ?> min · Lire →</span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </aside>
            <?php endif; ?>
        </article>

    </div>
</main>

<?php require __DIR__ . '/partials/footer.php'; ?>
