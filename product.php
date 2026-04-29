<?php
// Page produit individuelle : /cafetiere/{asin-or-slug}
declare(strict_types=1);
require __DIR__ . '/app/bootstrap.php';
use App\Core\Database;
use App\Core\Layout;

$cfg = Layout::loadConfig();
$id = $_GET['id'] ?? '';

if (!preg_match('/^[A-Z0-9a-z-]{1,80}$/', $id)) {
    http_response_code(404);
    $pageTitle = 'Produit introuvable · Cafetière à grain';
    require __DIR__ . '/partials/header.php';
    echo '<main class="container" style="padding:6rem 2rem;text-align:center;"><h1 class="display" style="font-size:3rem;">Produit introuvable</h1><p style="color:var(--muted);margin:1rem 0 2rem;">Cette fiche produit n\'existe pas.</p><a href="/blog" class="btn btn--primary">Voir les comparatifs</a></main>';
    require __DIR__ . '/partials/footer.php';
    exit;
}

$product = null;
try {
    $pdo = Database::pdo();
    $stmt = $pdo->prepare("SELECT * FROM products WHERE asin = :id1 OR slug = :id2 LIMIT 1");
    $stmt->execute([':id1' => $id, ':id2' => $id]);
    $product = $stmt->fetch();
} catch (Throwable $e) {
    error_log('product.php DB: ' . $e->getMessage());
}

if (!$product) {
    http_response_code(404);
    $pageTitle = 'Produit introuvable · Cafetière à grain';
    require __DIR__ . '/partials/header.php';
    echo '<main class="container" style="padding:6rem 2rem;text-align:center;"><h1 class="display" style="font-size:3rem;">Produit introuvable</h1><p style="color:var(--muted);margin:1rem 0 2rem;">Cette cafetière n\'est pas (encore) référencée.</p><a href="/blog" class="btn btn--primary">Voir nos comparatifs</a></main>';
    require __DIR__ . '/partials/footer.php';
    exit;
}

$images = !empty($product['images_json']) ? json_decode($product['images_json'], true) : [];
$features = !empty($product['features_json']) ? json_decode($product['features_json'], true) : [];
if (!is_array($images)) $images = [];
if (!is_array($features)) $features = [];

$relatedArticles = [];
try {
    $stmt = $pdo->prepare(
        "SELECT a.slug, a.title, a.description, a.publish_at FROM articles a
         INNER JOIN article_products ap ON ap.article_id = a.id
         WHERE ap.product_id = :pid AND a.status = 'published'
         ORDER BY ap.position ASC, a.publish_at DESC LIMIT 6"
    );
    $stmt->execute([':pid' => $product['id']]);
    $relatedArticles = $stmt->fetchAll();
} catch (Throwable $e) {}

$similarProducts = [];
try {
    $stmt = $pdo->prepare(
        "SELECT asin, slug, name, brand, price_eur, rating, main_image_url FROM products
         WHERE id != :id AND status = 'published'
         AND (brand = :brand OR ABS(price_eur - :price) < 200)
         ORDER BY rating DESC LIMIT 4"
    );
    $stmt->execute([':id' => $product['id'], ':brand' => $product['brand'] ?? '', ':price' => $product['price_eur'] ?? 0]);
    $similarProducts = $stmt->fetchAll();
} catch (Throwable $e) {}

$brand = $product['brand'] ?? '';
$name = $product['name'] ?? 'Cafetière';
$pageTitle = trim("$brand $name") . ' : test, prix et avis · Cafetière à grain';
$pageDescription = 'Test complet, prix Amazon, avis utilisateurs. ' . trim("$brand $name") . ' analysée sur 21 jours.';
$canonical = ($cfg['base_url'] ?? 'https://cafetiereagrain.fr') . '/cafetiere/' . urlencode($product['asin'] ?? $product['slug']);
$ogImage = $product['main_image_url'] ?? '';
$amazonTag = $cfg['affiliate']['amazon_tag'] ?? 'lacasamarke08-21';
$amazonUrl = 'https://www.amazon.fr/dp/' . urlencode($product['asin'] ?? '') . '?tag=' . urlencode($amazonTag);

require __DIR__ . '/partials/header.php';
?>

<main class="product-page">
    <div class="container">

        <?php
            // Breadcrumb : nom raccourci (retire doublon brand, coupe a la 1ere virgule, max 60 chars)
            $shortName = $name;
            if ($brand && stripos($shortName, $brand) === 0) {
                $shortName = trim(substr($shortName, strlen($brand)));
            }
            $cutPos = strpos($shortName, ',');
            if ($cutPos !== false && $cutPos > 8) $shortName = substr($shortName, 0, $cutPos);
            if (mb_strlen($shortName) > 60) $shortName = mb_substr($shortName, 0, 57) . '…';
            $crumbLabel = trim($brand ? "$brand $shortName" : $shortName);
            ?>
            <nav class="breadcrumb" aria-label="Fil d'Ariane">
                <a href="/">Accueil</a>
                <span class="breadcrumb__sep">›</span>
                <a href="/blog">Comparatifs</a>
                <span class="breadcrumb__sep">›</span>
                <span class="breadcrumb__current"><?= Layout::escape($crumbLabel) ?></span>
            </nav>

        <section class="product-hero">
            <div class="product-hero__media">
                <div class="product-hero__main-img">
                    <?php if (!empty($product['main_image_url'])): ?>
                        <img src="<?= Layout::escape($product['main_image_url']) ?>" alt="<?= Layout::escape(trim("$brand $name")) ?>" loading="eager" id="main-product-img">
                    <?php else: ?>
                        <div class="product-hero__no-img">Image indisponible</div>
                    <?php endif; ?>
                </div>

                <?php if (!empty($images) && count($images) > 1): ?>
                    <div class="product-hero__thumbs-wrapper">
                        <button class="thumbs-arrow thumbs-arrow--left" type="button" aria-label="Image précédente">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
                        </button>
                        <div class="product-hero__thumbs">
                            <?php foreach (array_slice($images, 0, 8) as $i => $img): ?>
                                <button class="product-hero__thumb<?= $i === 0 ? ' is-active' : '' ?>" data-img="<?= Layout::escape($img['link'] ?? $img) ?>" aria-label="Voir image <?= $i + 1 ?>">
                                    <img src="<?= Layout::escape($img['link'] ?? $img) ?>" alt="" loading="lazy">
                                </button>
                            <?php endforeach; ?>
                        </div>
                        <button class="thumbs-arrow thumbs-arrow--right" type="button" aria-label="Image suivante">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
                        </button>
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
                        <?php for ($i = 1; $i <= 5; $i++): $rate = (float)$product['rating']; $isFull = $i <= floor($rate); $isHalf = !$isFull && $i - 0.5 <= $rate; ?>
                            <span class="star<?= $isFull ? ' is-full' : ($isHalf ? ' is-half' : '') ?>">★</span>
                        <?php endfor; ?>
                        <span class="product-hero__rating-num"><?= number_format((float)$product['rating'], 1) ?>/5</span>
                        <?php if (!empty($product['ratings_total'])): ?>
                            <span class="product-hero__rating-count">(<?= number_format((int)$product['ratings_total'], 0, ',', ' ') ?> avis Amazon)</span>
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
                        <div class="product-hero__price-meta">Voir le prix actuel sur Amazon</div>
                    <?php endif; ?>
                </div>

                <a href="<?= Layout::escape($amazonUrl) ?>" target="_blank" rel="noopener sponsored" class="btn btn--primary btn--xl product-hero__cta">
                    Voir sur Amazon
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M7 17L17 7M17 7H7M17 7v10"/></svg>
                </a>
                <div class="product-hero__cta-meta">Lien partenaire · pas de surcoût · livraison Amazon</div>

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

        <?php if (!empty($product['description_long'])): ?>
            <section class="product-section">
                <h2 class="display">À propos de cette cafetière</h2>
                <div class="product-description"><?= nl2br(Layout::escape($product['description_long'])) ?></div>
            </section>
        <?php endif; ?>

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

        <?php if (!empty($similarProducts)): ?>
            <section class="product-section">
                <h2 class="display">Modèles similaires à comparer</h2>
                <div class="similar-grid">
                    <?php foreach ($similarProducts as $sim): ?>
                        <a class="similar-card" href="/cafetiere/<?= Layout::escape($sim['asin']) ?>">
                            <?php if (!empty($sim['main_image_url'])): ?>
                                <div class="similar-card__img"><img src="<?= Layout::escape($sim['main_image_url']) ?>" alt="<?= Layout::escape($sim['name']) ?>" loading="lazy"></div>
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

        <section class="product-cta-final">
            <h2 class="display">Prêt à passer au grain ?</h2>
            <p>Vérifie le prix actuel directement sur Amazon en un clic.</p>
            <a href="<?= Layout::escape($amazonUrl) ?>" target="_blank" rel="noopener sponsored" class="btn btn--primary btn--xl">Voir le prix actuel sur Amazon →</a>
        </section>

    </div>
</main>

<script>
(function(){
    document.querySelectorAll('.product-hero__thumb').forEach(function(t){
        t.addEventListener('click', function(){
            var img = t.dataset.img;
            if (img) {
                var main = document.getElementById('main-product-img');
                if (main) main.src = img;
                document.querySelectorAll('.product-hero__thumb').forEach(function(x){ x.classList.remove('is-active'); });
                t.classList.add('is-active');
            }
        });
    });
    document.querySelectorAll('.product-hero__thumbs-wrapper').forEach(function(w){
        var thumbs = w.querySelector('.product-hero__thumbs');
        var prev = w.querySelector('.thumbs-arrow--left');
        var next = w.querySelector('.thumbs-arrow--right');
        if (!thumbs) return;
        if (prev) prev.addEventListener('click', function(){ thumbs.scrollBy({left:-240,behavior:'smooth'}); });
        if (next) next.addEventListener('click', function(){ thumbs.scrollBy({left:240,behavior:'smooth'}); });
    });
})();
</script>

<?php require __DIR__ . '/partials/footer.php'; ?>
