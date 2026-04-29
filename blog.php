<?php
// Blog & Categories cafetiereagrain.fr
// URL : /blog (tous), /blog?cluster=xxx (par cluster), /categorie/xxx (alias)

declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

use App\Core\Database;
use App\Core\Layout;

$cfg = Layout::loadConfig();

// Cluster filter (si fourni : /blog?cluster=cafetiere-a-grain)
$cluster_slug = isset($_GET['cluster']) ? trim((string)$_GET['cluster']) : '';
$cluster_slug = preg_match('/^[a-z0-9-]{1,60}$/', $cluster_slug) ? $cluster_slug : '';

// Pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 12;
$offset = ($page - 1) * $perPage;

// Charger meta categorie
$cluster_meta = null;
$articles = [];
$total = 0;
$all_categories = [];
$top_products = [];

try {
    $pdo = Database::pdo();

    // Liste de toutes les categories pour la nav
    $all_categories = $pdo->query(
        "SELECT c.slug, c.name, c.description, c.icon, c.sort_order,
                (SELECT COUNT(*) FROM articles WHERE cluster = c.slug AND status = 'published') AS articles_count
         FROM categories c ORDER BY c.sort_order ASC"
    )->fetchAll();

    // Si cluster precise : recuperer la meta
    if ($cluster_slug) {
        $stmt = $pdo->prepare("SELECT * FROM categories WHERE slug = :s LIMIT 1");
        $stmt->execute([':s' => $cluster_slug]);
        $cluster_meta = $stmt->fetch();
    }

    // Articles (filtre cluster)
    if ($cluster_slug) {
        $stmt = $pdo->prepare(
            "SELECT a.slug, a.title, a.description, a.cluster, a.reading_time, a.publish_at, a.featured_image,
                    (SELECT p.main_image_url FROM article_products ap
                     JOIN products p ON p.id = ap.product_id
                     WHERE ap.article_id = a.id AND p.main_image_url IS NOT NULL
                     ORDER BY ap.position ASC LIMIT 1) AS product_image
             FROM articles a WHERE a.status = 'published' AND a.cluster = :c
             ORDER BY a.publish_at DESC LIMIT :lim OFFSET :off"
        );
        $stmt->bindValue(':c', $cluster_slug);
        $stmt->bindValue(':lim', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $articles = $stmt->fetchAll();

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM articles WHERE status = 'published' AND cluster = :c");
        $stmt->execute([':c' => $cluster_slug]);
        $total = (int)$stmt->fetchColumn();
    } else {
        $stmt = $pdo->prepare(
            "SELECT a.slug, a.title, a.description, a.cluster, a.reading_time, a.publish_at, a.featured_image,
                    (SELECT p.main_image_url FROM article_products ap
                     JOIN products p ON p.id = ap.product_id
                     WHERE ap.article_id = a.id AND p.main_image_url IS NOT NULL
                     ORDER BY ap.position ASC LIMIT 1) AS product_image
             FROM articles a WHERE a.status = 'published'
             ORDER BY a.publish_at DESC LIMIT :lim OFFSET :off"
        );
        $stmt->bindValue(':lim', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $articles = $stmt->fetchAll();
        $total = (int)$pdo->query("SELECT COUNT(*) FROM articles WHERE status = 'published'")->fetchColumn();
    }

    // Top 3 produits associes a cette categorie (via maillage interne)
    if ($cluster_slug && in_array($cluster_slug, ['cafetiere-a-grain', 'accessoires'])) {
        $top_products = $pdo->query(
            "SELECT DISTINCT p.asin, p.slug, p.name, p.brand, p.main_image_url, p.rating, p.price_eur, p.score_pertinence
             FROM products p
             INNER JOIN article_products ap ON ap.product_id = p.id
             INNER JOIN articles a ON a.id = ap.article_id
             WHERE p.status = 'published' AND a.cluster = " . $pdo->quote($cluster_slug) . "
             ORDER BY p.score_pertinence DESC LIMIT 3"
        )->fetchAll();
    } elseif (!$cluster_slug) {
        $top_products = $pdo->query(
            "SELECT asin, slug, name, brand, main_image_url, rating, price_eur, score_pertinence
             FROM products WHERE status = 'published'
             ORDER BY score_pertinence DESC LIMIT 3"
        )->fetchAll();
    }

} catch (Throwable $e) {
    error_log('blog.php DB error: ' . $e->getMessage());
}

$totalPages = max(1, (int)ceil($total / $perPage));

// SEO
if ($cluster_slug && $cluster_meta) {
    $pageTitle = $cluster_meta['name'] . ' — Cafetière à grain';
    $pageDescription = mb_substr($cluster_meta['description'] ?? '', 0, 160);
    $h1 = $cluster_meta['name'];
} else {
    $pageTitle = 'Tous les articles & comparatifs — Cafetière à grain';
    $pageDescription = 'Tous nos comparatifs, tests et guides sur les cafetières à grain : DeLonghi, Philips, Jura, Krups, café en grain, accessoires.';
    $h1 = 'Tous les articles';
}

require __DIR__ . '/partials/header.php';
?>

<main class="blog-page">
    <div class="container">

        <!-- Breadcrumb + H1 -->
        <nav class="breadcrumb">
            <a href="/">Accueil</a>
            <span class="breadcrumb__sep">›</span>
            <?php if ($cluster_slug): ?>
                <a href="/blog">Blog</a>
                <span class="breadcrumb__sep">›</span>
                <span class="breadcrumb__current"><?= Layout::escape($h1) ?></span>
            <?php else: ?>
                <span class="breadcrumb__current">Blog</span>
            <?php endif; ?>
        </nav>

        <header class="blog-header">
            <h1 class="display"><?= Layout::escape($h1) ?></h1>
            <?php if ($cluster_slug && $cluster_meta && !empty($cluster_meta['description'])): ?>
                <p class="blog-header__lead"><?= Layout::escape($cluster_meta['description']) ?></p>
            <?php else: ?>
                <p class="blog-header__lead"><?= $total ?> articles publiés. Tests sur 21 jours minimum, sans publicité déguisée.</p>
            <?php endif; ?>
        </header>

        <!-- Nav categories -->
        <nav class="categories-nav">
            <a href="/blog" class="categories-nav__item<?= !$cluster_slug ? ' is-active' : '' ?>">Tous</a>
            <?php foreach ($all_categories as $cat): ?>
                <a href="/categorie/<?= Layout::escape($cat['slug']) ?>"
                   class="categories-nav__item<?= $cluster_slug === $cat['slug'] ? ' is-active' : '' ?>">
                    <?= Layout::escape($cat['name']) ?>
                    <span class="categories-nav__count"><?= (int)$cat['articles_count'] ?></span>
                </a>
            <?php endforeach; ?>
        </nav>

        <!-- TOP produits dans cette categorie (maillage) -->
        <?php if (!empty($top_products)): ?>
            <section class="blog-top-products">
                <h2 class="display">Top 3 du moment <?= $cluster_slug ? 'dans cette catégorie' : '' ?></h2>
                <div class="blog-top-products__grid">
                    <?php foreach ($top_products as $i => $p): ?>
                        <a class="mini-product" href="/cafetiere/<?= Layout::escape($p['asin']) ?>">
                            <span class="mini-product__rank">#<?= $i + 1 ?></span>
                            <?php if (!empty($p['main_image_url'])): ?>
                                <img src="<?= Layout::escape($p['main_image_url']) ?>" alt="" loading="lazy">
                            <?php endif; ?>
                            <div>
                                <div class="mini-product__brand"><?= Layout::escape($p['brand']) ?></div>
                                <div class="mini-product__name"><?= Layout::escape(mb_substr($p['name'], 0, 50)) ?></div>
                                <div class="mini-product__price">★ <?= number_format((float)$p['rating'], 1) ?>/5 · <?= number_format((float)$p['price_eur'], 0, ',', ' ') ?> €</div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <!-- Liste articles -->
        <?php if (empty($articles)): ?>
            <div class="empty-state">
                <h2>Aucun article ici</h2>
                <p>Les articles de cette catégorie arrivent. <a href="/blog">Voir tous les articles</a>.</p>
            </div>
        <?php else: ?>
            <section class="articles-grid articles-grid--blog">
                <?php foreach ($articles as $art): ?>
                    <a class="article-card" href="/<?= Layout::escape($art['slug']) ?>">
                        <?php
                            // Vignette : 1) featured_image  2) photo produit principal  3) SVG genere
                            $thumbSrc = $art['featured_image']
                                ?? null;
                            if (!$thumbSrc && !empty($art['product_image'])) $thumbSrc = $art['product_image'];
                            if (!$thumbSrc) $thumbSrc = '/blog/' . urlencode($art['slug']) . '.svg';
                            $isProductImg = !empty($art['product_image']) && empty($art['featured_image']);
                        ?>
                        <div class="article-card__cover<?= $isProductImg ? ' article-card__cover--product' : '' ?>">
                            <img src="<?= Layout::escape($thumbSrc) ?>" alt="<?= Layout::escape($art['title']) ?>" loading="lazy">
                        </div>
                        <div class="article-card__body">
                            <span class="article-card__cluster"><?= Layout::escape($art['cluster']) ?></span>
                            <h3 class="article-card__title"><?= Layout::escape($art['title']) ?></h3>
                            <?php if (!empty($art['description'])): ?>
                                <p class="article-card__excerpt"><?= Layout::escape(mb_substr($art['description'], 0, 140)) ?>…</p>
                            <?php endif; ?>
                            <div class="article-card__meta"><?= (int)$art['reading_time'] ?> min · <?= !empty($art['publish_at']) ? date('j M Y', strtotime($art['publish_at'])) : '' ?></div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </section>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <nav class="pagination">
                    <?php $base = $cluster_slug ? '/blog?cluster=' . urlencode($cluster_slug) . '&' : '/blog?'; ?>
                    <?php if ($page > 1): ?>
                        <a href="<?= Layout::escape($base . 'page=' . ($page - 1)) ?>" class="pagination__prev">← Précédent</a>
                    <?php endif; ?>
                    <span class="pagination__info">Page <?= $page ?> / <?= $totalPages ?></span>
                    <?php if ($page < $totalPages): ?>
                        <a href="<?= Layout::escape($base . 'page=' . ($page + 1)) ?>" class="pagination__next">Suivant →</a>
                    <?php endif; ?>
                </nav>
            <?php endif; ?>
        <?php endif; ?>

    </div>
</main>

<?php require __DIR__ . '/partials/footer.php'; ?>
