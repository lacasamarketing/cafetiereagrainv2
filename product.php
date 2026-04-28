<?php
// Page produit individuelle : /cafetiere/{asin-or-slug}
// Affiche fiche complete avec photos Rainforest, prix temps reel, articles lies.

declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

use App\Core\Database;
use App\Core\Layout;

$cfg = Layout::loadConfig();
$id = $_GET['id'] ?? '';

// Validation : ASIN (10 chars alphanum maj) ou slug
if (!preg_match('/^[A-Z0-9a-z-]{1,80}$/', $id)) {
    http_response_code(404);
    $pageTitle = 'Produit introuvable · Cafetière à grain';
    require __DIR__ . '/partials/header.php';
    ?>
    <main class="container" style="padding: 6rem 2rem; text-align: center;">
        <h1 class="display" style="font-size: 3rem;">Produit introuvable</h1>
        <p style="color: var(--muted); margin: 1rem 0 2rem;">Cette fiche produit n'existe pas ou a été retirée du catalogue.</p>
        <a href="/blog" class="btn btn--primary">Voir tous les comparatifs</a>
    </main>
    <?php
    require __DIR__ . '/partials/footer.php';
    exit;
}

// Recuperer le produit depuis la DB
$product = null;
try {
    $pdo = Database::pdo();
    // Recherche par ASIN exact OU slug
    $stmt = $pdo->prepare(
        "SELECT * FROM products WHERE asin = :id OR slug = :id LIMIT 1"
    );
    $stmt->execute([':id' => $id]);
    $product = $stmt->fetch();
} catch (Throwable $e) {
    // DB non accessible -> 404 propre
    error_log('product.php DB error: ' . $e->getMessage());
}

if (!$product) {
    http_response_code(404);
    $pageTitle = 'Produit introuvable · Cafetière à grain';
    require __DIR__ . '/partials/header.php';
    ?>
    <main class="container" style="padding: 6rem 2rem; text-align: center;">
        <h1 class="display" style="font-size: 3rem;">Produit introuvable</h1>
        <p style="color: var(--muted); margin: 1rem 0 2rem;">Cette cafetière n'est pas (encore) référencée dans notre comparatif.</p>
        <a href="/blog" class="btn btn--primary">Voir nos comparatifs</a>
    </main>
    <?php
    require __DIR__ . '/partials/footer.php';
    exit;
}

// Decode JSON champs (images, features, etc.)
$images = !empty($product['images_json']) ? json_decode($product['images_json'], true) : [];
$features = !empty($product['features_json']) ? json_decode($product['features_json'], true) : [];
$gallery = !empty($product['gallery_json']) ? json_decode($product['gallery_json'], true) : [];

if (!is_array($images)) $images = [];
if (!is_array($features)) $features = [];
if (!is_array($gallery)) $gallery = [];

// Articles qui parlent de ce produit
$relatedArticles = [];
try {
    $stmt = $pdo->prepare(
        "SELECT a.slug, a.title, a.description, a.publish_at
         FROM articles a
         INNER JOIN article_products ap ON ap.article_id = a.id
         WHERE ap.product_id = :pid AND a.status = 'published'
         ORDER BY ap.position ASC, a.publish_at DESC
         LIMIT 6"
    );
    $stmt->execute([':pid' => $product['id']]);
    $relatedArticles = $stmt->fetchAll();
} catch (Throwable $e) { /* fallback gracieux */ }

// Produits similaires (memes mots-cles brand, prix proche)
$similarProducts = [];
try {
    $stmt = $pdo->prepare(
        "SELECT asin, slug, name, brand, price_eur, rating, main_image_url
         FROM products
         WHERE id != :id AND status = 'published'
         AND (brand = :brand OR ABS(price_eur - :price) < 200)
         ORDER BY rating DESC LIMIT 4"
    );
    $stmt->execute([
        ':id' => $product['id'],
        ':brand' => $product['brand'] ?? '',
        ':price' => $product['price_eur'] ?? 0,
    ]);
    $similarProducts = $stmt->fetchAll();
} catch (Throwable $e) { /* fallback */ }

// SEO
$brand = $product['brand'] ?? '';
$name = $product['name'] ?? 'Cafetière';
$pageTitle = trim("$brand $name") . ' : test, prix Amazon et avis · Cafetière à grain';
$pageDescription = 'Test complet, prix Amazon temps réel, avis utilisateurs vérifiés. ' . trim("$brand $name") . ' analysée sur 21 jours.';
$canonical = ($cfg['base_url'] ?? 'https://cafetiereagrain.fr') . '/cafetiere/' . urlencode($product['asin'] ?? $product['slug']);
$ogImage = $product['main_image_url'] ?? '';

// CTA Amazon avec tag affilie
$amazonTag = $cfg['affiliate']['amazon_tag'] ?? 'lacasamarke08-21';
$amazonUrl = 'https://www.amazon.fr/dp/' . urlencode($product['asin'] ?? '') . '?tag=' . urlencode($amazonTag);

require __DIR__ . '/partials/header.php';
?>

<!-- JSON-LD Product Schema (SEO Google rich results) -->
<script type="application/ld+json"><?= json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'Product',
    'name' => trim("$brand $name"),
    'image' => $product['main_image_url'] ?? '',
    'brand' => ['@type' => 'Brand', 'name' => $brand],
    'sku' => $product['asin'] ?? '',
    'offers' => [
        '@type' => 'Offer',
        'url' => $amazonUrl,
        'priceCurrency' => 'EUR',
        'price' => $product['price_eur'] ?? 0,
        'availability' => 'https://schema.org/InStock',
    ],
    'aggregateRating' => $product['rating'] ? [
        '@type' => 'AggregateRating',
        'ratingValue' => $product['rating'],
        'reviewCount' => $product['reviews_count'] ?? 1,
    ] : null,
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?></script>

<main class="product-page">
    <div class="container">

        <!-- Breadcrumb -->
        <nav class="breadcrumb" aria-label="Fil d'Ariane">
            <a href="/">Accueil</a>
            <span class="breadcrumb__sep">›</span>
            <a href="/blog">Comparatifs</a>
            <span class="breadcrumb__sep">›</span>
            <span class="breadcrumb__current"><?= Layout::escape(trim("$brand $name")) ?></span>
        </nav>

        <!-- HERO PRODUIT : galerie + infos cles -->
        <section class="product-hero">
            <div class="product-hero__gallery">
                <div class="product-hero__main-img">
                    <?php if (!empty($product['main_image_url'])): ?>
                        <img src="<?= Layout::escape($product['main_image_url']) ?>"
                             alt="<?= Layout::escape(trim("$brand $name")) ?>"
                             loading="eager" id="main-product-img">
                    <?php else: ?>
                        <div class="product-hero__no-img">Image indisponible</div>
                    <?php endif; ?>
                </div>
                <?php if (!empty($images) && count($images) > 1): ?>
                    <div class="product-hero__thumbs">
                        <?php foreach (array_slice($images, 0, 6) as $i => $img): ?>
                            <button class="product-hero__thumb<?= $i === 0 ? ' is-active' : '' ?>"
                                    data-img="<?= Layout::escape($img['link'] ?? $img) ?>"
                                    aria-label="Voir image <?= $i + 1 ?>">
                                <img src="<?= Layout::escape($img['link'] ?? $img) ?>"
                                     alt="" loading="lazy">
                            </button>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="product-hero__info">
                <?php if ($brand): ?>
                    <div class="product-hero__brand"><?= Layout::escape($brand) ?></div>
                <?php endif; ?>
                <h1 class="product-hero__title display"><?= Layout::escape($name) ?></h1>

                <?php if (!empty($product['rating'])): ?>
                    <div class="product-hero__rating">
                        <?php for ($i = 1; $i <= 5; $i++):
                            $rate = (float)$product['rating'];
                            $isFull = $i <= floor($rate);
                            $isHalf = !$isFull && $i - 0.5 <= $rate;
                        ?>
                            <span class="star<?= $isFull ? ' is-full' : ($isHalf ? ' is-half' : '') ?>">★</span>
                        <?php endfor; ?>
                        <span class="product-hero__rating-num"><?= number_format((float)$product['rating'], 1) ?>/5</span>
                        <?php if (!empty($product['reviews_count'])): ?>
                            <span class="product-hero__rating-count">(<?= number_format((int)$product['reviews_count'], 0, ',', ' ') ?> avis Amazon)</span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($product['verdict'])): ?>
                    <p class="product-hero__verdict"><?= Layout::escape($product['verdict']) ?></p>
                <?php endif; ?>

                <div class="product-hero__price-block">
                    <?php if (!empty($product['price_eur'])): ?>
                        <div class="product-hero__price">
                            <span class="price-now"><?= number_format((float)$product['price_eur'], 0, ',', ' ') ?> €</span>
                            <?php if (!empty($product['price_old']) && $product['price_old'] > $product['price_eur']): ?>
                                <span class="price-old"><?= number_format((float)$product['price_old'], 0, ',', ' ') ?> €</span>
                            <?php endif; ?>
                        </div>
                        <div class="product-hero__price-meta">Prix Amazon mis à jour aujourd'hui</div>
                    <?php endif; ?>
                </div>

                <a href="<?= Layout::escape($amazonUrl) ?>" target="_blank" rel="noopener sponsored"
                   class="btn btn--primary btn--xl product-hero__cta">
                    Voir sur Amazon
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M7 17L17 7M17 7H7M17 7v10"/></svg>
                </a>
                <div class="product-hero__cta-meta">
                    Lien partenaire · pas de surcoût pour toi · livraison Amazon
                </div>

                <?php if (!empty($features)): ?>
                    <div class="product-hero__features">
                        <h3>Points clés</h3>
                        <ul>
                            <?php foreach (array_slice($features, 0, 5) as $feat): ?>
                                <li><?= Layout::escape((string)$feat) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- DESCRIPTION & SPECS -->
        <?php if (!empty($product['description'])): ?>
            <section class="product-section">
                <h2 class="display">À propos de cette cafetière</h2>
                <div class="product-description">
                    <?= nl2br(Layout::escape($product['description'])) ?>
                </div>
            </section>
        <?php endif; ?>

        <!-- ARTICLES LIES -->
        <?php if (!empty($relatedArticles)): ?>
            <section class="product-section">
                <h2 class="display">Nos articles sur cette cafetière</h2>
                <div class="related-grid">
                    <?php foreach ($relatedArticles as $art): ?>
                        <a class="related-card" href="/<?= Layout::escape($art['slug']) ?>">
                            <h3><?= Layout::escape($art['title']) ?></h3>
                            <?php if (!empty($art['description'])): ?>
                                <p><?= Layout::escape(mb_substr(strip_tags($art['description']), 0, 140)) ?>…</p>
                            <?php endif; ?>
                            <span class="related-card__cta">Lire l'article →</span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <!-- PRODUITS SIMILAIRES -->
        <?php if (!empty($similarProducts)): ?>
            <section class="product-section">
                <h2 class="display">Modèles similaires à comparer</h2>
                <div class="similar-grid">
                    <?php foreach ($similarProducts as $sim): ?>
                        <a class="similar-card" href="/cafetiere/<?= Layout::escape($sim['asin']) ?>">
                            <?php if (!empty($sim['main_image_url'])): ?>
                                <div class="similar-card__img">
                                    <img src="<?= Layout::escape($sim['main_image_url']) ?>"
                                         alt="<?= Layout::escape($sim['name']) ?>" loading="lazy">
                                </div>
                            <?php endif; ?>
                            <div class="similar-card__brand"><?= Layout::escape($sim['brand']) ?></div>
                            <h3 class="similar-card__name"><?= Layout::escape($sim['name']) ?></h3>
                            <?php if (!empty($sim['rating'])): ?>
                                <div class="similar-card__rating">★ <?= number_format((float)$sim['rating'], 1) ?>/5</div>
                            <?php endif; ?>
                            <?php if (!empty($sim['price_eur'])): ?>
                                <div class="similar-card__price"><?= number_format((float)$sim['price_eur'], 0, ',', ' ') ?> €</div>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <!-- CTA FINAL -->
        <section class="product-cta-final">
            <h2 class="display">Prêt à passer au grain ?</h2>
            <p>Le prix sur Amazon est mis à jour automatiquement. Tu peux vérifier en un clic.</p>
            <a href="<?= Layout::escape($amazonUrl) ?>" target="_blank" rel="noopener sponsored" class="btn btn--primary btn--xl">
                Voir le prix actuel sur Amazon →
            </a>
        </section>

    </div>
</main>

<script>
// Galerie : changement d'image au click sur thumb
document.querySelectorAll('.product-hero__thumb').forEach(t => {
    t.addEventListener('click', () => {
        const img = t.dataset.img;
        if (img) {
            document.getElementById('main-product-img').src = img;
            document.querySelectorAll('.product-hero__thumb').forEach(x => x.classList.remove('is-active'));
            t.classList.add('is-active');
        }
    });
});
</script>

<?php require __DIR__ . '/partials/footer.php'; ?>
