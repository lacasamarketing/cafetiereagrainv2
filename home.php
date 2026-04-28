<?php
// Homepage complete cafetiereagrain.fr - hero 3D + TOPs intelligents + maillage interne
// Active quand DB peuplee. Tant que pas pret : .htaccess maintenance route vers index.php (attente).

declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

use App\Core\Database;
use App\Core\Layout;

$cfg = Layout::loadConfig();

$top_pertinence = [];
$top_ventes = [];
$top_par_type = [];
$articles_recents = [];
$articles_par_categorie = [];

try {
    $pdo = Database::pdo();

    // TOP 3 PAR PERTINENCE (rating x log(reviews))
    $top_pertinence = $pdo->query(
        "SELECT asin, slug, name, brand, type_cafetiere, price_eur, rating, ratings_total,
                main_image_url, score_pertinence, summary_avis, verdict
         FROM products
         WHERE status = 'published' AND rating IS NOT NULL AND rating >= 4
         ORDER BY score_pertinence DESC LIMIT 3"
    )->fetchAll();

    // TOP 3 PAR VENTE (Amazon bestsellers rank)
    $top_ventes = $pdo->query(
        "SELECT asin, slug, name, brand, type_cafetiere, price_eur, rating, ratings_total,
                main_image_url, bestsellers_rank, bestsellers_cat
         FROM products
         WHERE status = 'published' AND bestsellers_rank IS NOT NULL
         ORDER BY score_vente DESC LIMIT 3"
    )->fetchAll();

    // TOP par typologie : 1 representant par type
    $stmt = $pdo->query(
        "SELECT p.* FROM products p
         INNER JOIN (
             SELECT type_cafetiere, MAX(score_pertinence) AS max_score
             FROM products WHERE status = 'published' GROUP BY type_cafetiere
         ) m ON p.type_cafetiere = m.type_cafetiere AND p.score_pertinence = m.max_score
         WHERE p.status = 'published' ORDER BY p.score_pertinence DESC LIMIT 6"
    );
    $top_par_type = $stmt->fetchAll();

    // 6 articles recents
    $articles_recents = $pdo->query(
        "SELECT slug, title, description, cluster, reading_time, publish_at
         FROM articles WHERE status = 'published'
         ORDER BY publish_at DESC LIMIT 6"
    )->fetchAll();

    // Articles groupes par categorie pour maillage
    $stmt = $pdo->query(
        "SELECT a.slug, a.title, a.cluster, a.reading_time
         FROM articles a WHERE a.status = 'published'
         ORDER BY a.cluster, a.publish_at DESC"
    );
    foreach ($stmt as $row) {
        $articles_par_categorie[$row['cluster']][] = $row;
    }
} catch (Throwable $e) {
    error_log('home.php DB error: ' . $e->getMessage());
}

// Compteurs
$count_products = 0; $count_articles = 0;
try {
    $count_products = (int)$pdo->query("SELECT COUNT(*) FROM products WHERE status = 'published'")->fetchColumn();
    $count_articles = (int)$pdo->query("SELECT COUNT(*) FROM articles WHERE status = 'published'")->fetchColumn();
} catch (Throwable $e) {}

// Helpers d'affichage
function type_label(string $t): string {
    return [
        'espresso_broyeur_auto' => 'Espresso broyeur automatique',
        'espresso_manuel'       => 'Expresso manuel',
        'expresso_capsule'      => 'Capsule',
        'hybride_grain_filtre'  => 'Hybride grain + filtre',
        'moulin'                => 'Moulin a cafe',
        'accessoire'            => 'Accessoire',
        'autre'                 => 'Autre',
    ][$t] ?? ucfirst($t);
}
function type_emoji(string $t): string {
    return ['espresso_broyeur_auto' => '☕', 'espresso_manuel' => '🤚', 'moulin' => '⚙️', 'hybride_grain_filtre' => '🔄', 'accessoire' => '🔧'][$t] ?? '☕';
}
function stars_render(?float $r): string {
    if ($r === null) return '';
    $out = '';
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= floor($r)) $out .= '<span class="star is-full">★</span>';
        elseif ($i - 0.5 <= $r) $out .= '<span class="star is-half">★</span>';
        else $out .= '<span class="star">★</span>';
    }
    return $out;
}
function amazon_url(array $product, array $cfg): string {
    $tag = $cfg['affiliate']['amazon_tag'] ?? 'lacasamarke08-21';
    return 'https://www.amazon.fr/dp/' . urlencode($product['asin']) . '?tag=' . urlencode($tag);
}

$pageTitle = 'Cafetière à grain — Le comparatif obsessionnel 2026';
$pageDescription = 'Comparatif indépendant des meilleures cafetières à grain : DeLonghi, Philips, Jura, Krups. Prix Amazon temps réel, tests sur 21 jours minimum.';

require __DIR__ . '/partials/header.php';
?>

<main class="home">

    <!-- ============= HERO ============= -->
    <section class="hero">
        <div class="hero__decor">
            <div class="bean b1"></div>
            <div class="bean b2"></div>
            <div class="bean b3"></div>
            <div class="bean b4"></div>
            <div class="bean b5"></div>
            <div class="bean b6"></div>
            <div class="bean b7"></div>
            <div class="bean b8"></div>
        </div>
        <div class="container hero__inner">
            <div class="hero__content">
                <div class="hero__eyebrow">PRIX TEMPS RÉEL · <?= $count_products ?:'50' ?>+ MACHINES TESTÉES</div>
                <h1 class="hero__title display">Trouve <span class="hero__accent">la cafetière à grain</span> qui te ressemble.</h1>
                <p class="hero__lead">Avis honnêtes, comparatifs sans concession, prix Amazon mis à jour chaque heure. On a testé les meilleures machines pour t'éviter les mauvaises surprises à 800 €.</p>
                <div class="hero__ctas">
                    <a href="#top-cafetieres" class="btn btn--primary btn--xl">Voir le top 2026 →</a>
                    <a href="#methode" class="btn btn--ghost">Comment on teste ↓</a>
                </div>
                <div class="hero__stats">
                    <div class="stat"><span class="stat__num"><?= $count_products ?: '20+' ?></span><span class="stat__lbl">Machines testées</span></div>
                    <div class="stat"><span class="stat__num"><?= $count_articles ?: '50+' ?></span><span class="stat__lbl">Articles publiés</span></div>
                    <div class="stat"><span class="stat__num">21j</span><span class="stat__lbl">Test minimum</span></div>
                </div>
            </div>
            <div class="hero__cup3d" id="cup-3d-container"></div>
        </div>
    </section>

    <!-- ============= MARQUEE ============= -->
    <div class="marquee" aria-hidden="true">
        <div class="marquee__track">
            <?php for ($i = 0; $i < 2; $i++): ?>
                <span class="marquee__item">Cafetière à grain</span>
                <span class="marquee__item marquee__item--accent">Espresso barista</span>
                <span class="marquee__item">Café fraîchement moulu</span>
                <span class="marquee__item marquee__item--accent">Broyeur céramique</span>
                <span class="marquee__item">Crema dorée</span>
                <span class="marquee__item marquee__item--accent">Arabica · Robusta</span>
                <span class="marquee__item">Tests sur 21 jours</span>
                <span class="marquee__item marquee__item--accent">Prix Amazon temps réel</span>
            <?php endfor; ?>
        </div>
    </div>

    <!-- ============= FEATURES BAR ============= -->
    <div class="features-bar">
        <div class="container features-bar__row">
            <div class="feat"><span class="feat__icon">★</span>Notes vérifiées Amazon</div>
            <div class="feat"><span class="feat__icon">€</span>Prix mis à jour chaque heure</div>
            <div class="feat"><span class="feat__icon">✓</span>Tests sur 21 jours minimum</div>
            <div class="feat"><span class="feat__icon">↻</span>Remboursable 30 jours Amazon</div>
        </div>
    </div>

    <!-- ============= TOP PAR PERTINENCE ============= -->
    <?php if (!empty($top_pertinence)): ?>
    <section class="section" id="top-cafetieres">
        <div class="container">
            <div class="section-header">
                <div class="section-eyebrow">Top du moment</div>
                <h2 class="section-title display">Les cafetières qui valent leur prix.</h2>
                <p class="section-subtitle">Sélection 2026 par <strong>score de pertinence</strong> (note × volume d'avis vérifiés Amazon). Aucun produit n'est sponsorisé.</p>
            </div>
            <div class="products-grid">
                <?php foreach ($top_pertinence as $i => $p): ?>
                    <a class="product-card" href="/cafetiere/<?= Layout::escape($p['asin']) ?>" data-tilt>
                        <div class="product-card__rank">#<?= $i + 1 ?></div>
                        <?php if (!empty($p['main_image_url'])): ?>
                            <div class="product-card__img">
                                <img src="<?= Layout::escape($p['main_image_url']) ?>" alt="<?= Layout::escape($p['name']) ?>" loading="lazy">
                            </div>
                        <?php endif; ?>
                        <div class="product-card__brand"><?= Layout::escape($p['brand']) ?></div>
                        <h3 class="product-card__name"><?= Layout::escape(mb_substr($p['name'], 0, 80)) ?></h3>
                        <div class="product-card__rating"><?= stars_render((float)$p['rating']) ?> <span><?= number_format((float)$p['rating'], 1) ?>/5 · <?= number_format((int)$p['ratings_total'], 0, ',', ' ') ?> avis</span></div>
                        <?php if (!empty($p['price_eur'])): ?>
                            <div class="product-card__price"><span class="price-now"><?= number_format((float)$p['price_eur'], 0, ',', ' ') ?> €</span></div>
                        <?php endif; ?>
                        <span class="product-card__cta">Voir le test complet →</span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- ============= PAR TYPOLOGIE ============= -->
    <?php if (!empty($top_par_type)): ?>
    <section class="section section--alt">
        <div class="container">
            <div class="section-header">
                <div class="section-eyebrow">Par typologie</div>
                <h2 class="section-title display">Quelle machine pour quel usage ?</h2>
                <p class="section-subtitle">Espresso broyeur automatique, expresso manuel, hybride : chaque type répond à un besoin précis.</p>
            </div>
            <div class="typologies">
                <?php foreach ($top_par_type as $p): ?>
                    <a class="typology-card" href="/cafetiere/<?= Layout::escape($p['asin']) ?>">
                        <div class="typology-card__type"><?= type_emoji($p['type_cafetiere']) ?> <?= type_label($p['type_cafetiere']) ?></div>
                        <?php if (!empty($p['main_image_url'])): ?>
                            <div class="typology-card__img"><img src="<?= Layout::escape($p['main_image_url']) ?>" alt="" loading="lazy"></div>
                        <?php endif; ?>
                        <div class="typology-card__brand"><?= Layout::escape($p['brand']) ?></div>
                        <h3 class="typology-card__name"><?= Layout::escape(mb_substr($p['name'], 0, 60)) ?></h3>
                        <?php if (!empty($p['rating'])): ?>
                            <div class="typology-card__rating">★ <?= number_format((float)$p['rating'], 1) ?>/5</div>
                        <?php endif; ?>
                        <?php if (!empty($p['price_eur'])): ?>
                            <div class="typology-card__price"><?= number_format((float)$p['price_eur'], 0, ',', ' ') ?> €</div>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- ============= TOP PAR VENTE ============= -->
    <?php if (!empty($top_ventes)): ?>
    <section class="section">
        <div class="container">
            <div class="section-header">
                <div class="section-eyebrow">Plus achetées</div>
                <h2 class="section-title display">Les cafetières qui cartonnent en ce moment.</h2>
                <p class="section-subtitle">Classement Amazon France <em>Machines à café automatiques</em>. Mis à jour automatiquement.</p>
            </div>
            <div class="bestsellers">
                <?php foreach ($top_ventes as $i => $p): ?>
                    <a class="bestseller" href="/cafetiere/<?= Layout::escape($p['asin']) ?>">
                        <div class="bestseller__num">#<?= $p['bestsellers_rank'] ?></div>
                        <?php if (!empty($p['main_image_url'])): ?>
                            <div class="bestseller__img"><img src="<?= Layout::escape($p['main_image_url']) ?>" alt="" loading="lazy"></div>
                        <?php endif; ?>
                        <div class="bestseller__info">
                            <div class="bestseller__brand"><?= Layout::escape($p['brand']) ?></div>
                            <h3 class="bestseller__name"><?= Layout::escape(mb_substr($p['name'], 0, 70)) ?></h3>
                            <div class="bestseller__meta">
                                <span>★ <?= number_format((float)$p['rating'], 1) ?>/5</span>
                                <span class="bestseller__price"><?= number_format((float)$p['price_eur'], 0, ',', ' ') ?> €</span>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- ============= ARTICLES RECENTS ============= -->
    <?php if (!empty($articles_recents)): ?>
    <section class="section section--alt">
        <div class="container">
            <div class="section-header">
                <div class="section-eyebrow">Le blog</div>
                <h2 class="section-title display">Nos derniers articles &amp; tests.</h2>
            </div>
            <div class="articles-grid">
                <?php foreach ($articles_recents as $art): ?>
                    <a class="article-card" href="/<?= Layout::escape($art['slug']) ?>">
                        <span class="article-card__cluster"><?= Layout::escape($art['cluster']) ?></span>
                        <h3 class="article-card__title"><?= Layout::escape($art['title']) ?></h3>
                        <?php if (!empty($art['description'])): ?>
                            <p class="article-card__excerpt"><?= Layout::escape(mb_substr($art['description'], 0, 140)) ?>…</p>
                        <?php endif; ?>
                        <span class="article-card__meta"><?= (int)$art['reading_time'] ?> min de lecture →</span>
                    </a>
                <?php endforeach; ?>
            </div>
            <div style="text-align: center; margin-top: 2rem;">
                <a href="/blog" class="btn btn--ghost">Tous les articles →</a>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- ============= MAILLAGE INTERNE PAR CATEGORIE ============= -->
    <?php if (!empty($articles_par_categorie)): ?>
    <section class="section">
        <div class="container">
            <div class="section-header">
                <div class="section-eyebrow">Toutes les rubriques</div>
                <h2 class="section-title display">Explore par thème.</h2>
            </div>
            <div class="categories-grid">
                <?php
                $categoriesMeta = [
                    'cafetiere-a-grain' => ['Cafetière à grain', 'Tests des meilleures machines DeLonghi, Philips, Jura, Krups, Saeco.'],
                    'cafes-en-grain'    => ['Cafés en grain',    'Origines, variétés et torréfactions : Arabica, Robusta, ethiopien, colombien.'],
                    'accessoires'       => ['Accessoires',       'Tasses, moulins, balances, accessoires barista.'],
                    'conseils-budget'   => ['Conseils & budget', 'Coût annuel, comparatifs grain vs dosette, choix d\'entretien.'],
                ];
                foreach ($categoriesMeta as $slug => $meta):
                    $arts = $articles_par_categorie[$slug] ?? [];
                    if (empty($arts)) continue;
                ?>
                    <div class="category-block">
                        <h3 class="category-block__title"><?= Layout::escape($meta[0]) ?></h3>
                        <p class="category-block__desc"><?= Layout::escape($meta[1]) ?></p>
                        <ul class="category-block__list">
                            <?php foreach (array_slice($arts, 0, 5) as $art): ?>
                                <li><a href="/<?= Layout::escape($art['slug']) ?>"><?= Layout::escape($art['title']) ?></a></li>
                            <?php endforeach; ?>
                        </ul>
                        <a href="/categorie/<?= Layout::escape($slug) ?>" class="category-block__more">Voir toute la catégorie →</a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- ============= METHODE / EDITORIAL ============= -->
    <section class="section section--alt" id="methode">
        <div class="container">
            <div class="comp-grid">
                <div>
                    <div class="section-eyebrow">Comment on teste</div>
                    <h2 class="section-title display">Notre méthodologie sans tabou.</h2>
                    <p style="margin-top: 1rem; color: var(--muted); line-height: 1.7;">Chaque machine passe entre nos mains pendant 21 jours minimum. On note 5 critères essentiels, on photographie les vrais expressos, on teste l'entretien quotidien dans des conditions réelles.</p>
                </div>
                <div class="comp-list">
                    <div class="comp-item"><span class="comp-num">1</span><div><strong>Qualité d'extraction</strong><span>Crema, température, corps en bouche.</span></div></div>
                    <div class="comp-item"><span class="comp-num">2</span><div><strong>Bruit du broyeur</strong><span>Mesure décibels en conditions réelles.</span></div></div>
                    <div class="comp-item"><span class="comp-num">3</span><div><strong>Facilité d'entretien</strong><span>Détartrage, nettoyage groupe café.</span></div></div>
                    <div class="comp-item"><span class="comp-num">4</span><div><strong>Rapport qualité/prix</strong><span>Coût total possession sur 5 ans.</span></div></div>
                </div>
            </div>
        </div>
    </section>

</main>

<script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
<script src="/assets/js/coffee-cup-3d.js" defer></script>
<script src="/assets/js/coffee-beans-floating.js" defer></script>
<script src="/assets/js/marquee.js" defer></script>

<?php require __DIR__ . '/partials/footer.php'; ?>
