<?php
// Page produit / fiche test cafetiereagrain.fr
// URL: /tests/{slug} -> route via .htaccess vers test.php?slug={slug}

declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

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
    if (preg_match('#^/tests/([a-z0-9\-]+)/?$#i', $_path, $_m)) {
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
    $stmt = $pdo->prepare("SELECT * FROM products WHERE slug = ? AND status = 'published' LIMIT 1");
    $stmt->execute([$slug]);
    $p = $stmt->fetch();
} catch (Throwable $e) {
    $p = null;
}

if (!$p) {
    http_response_code(404);
    $pageTitle = 'Test introuvable · Cafetière à grain';
    require __DIR__ . '/partials/header.php';
    echo '<main class="article-wrap"><div class="container"><a href="/blog" class="article-back">← Tous les tests</a><h1 class="article-h1">404 · Test introuvable.</h1><p class="text-muted">Cette fiche produit n\'existe pas. Tu peux <a href="/blog?cluster=test" class="text-uv">consulter tous nos tests</a>.</p></div></main>';
    require __DIR__ . '/partials/footer.php';
    exit;
}

$pros = !empty($p['pros']) ? (json_decode($p['pros'], true) ?: []) : [];
$cons = !empty($p['cons']) ? (json_decode($p['cons'], true) ?: []) : [];

// SEO : title + meta optimisés autour du nom produit + mot-clé principal
$pageTitle = $p['name'] . ' : avis, prix et comparatif cafetière à grains 2026';
$pageDescription = ($p['verdict'] ? $p['verdict'] . ' ' : '') . 'Analyse complète du cafetière à grains ' . $p['name'] . ' : surface couverte, technologie, prix, retours utilisateurs.';
$pageDescription = mb_substr($pageDescription, 0, 158);
$canonical = ($cfg['base_url'] ?? '') . '/tests/' . $p['slug'];

// Maillage interne : autres produits du même target_use + articles qui citent le produit
$relatedProducts = [];
$relatedArticles = [];
try {
    if (!empty($p['target_use'])) {
        $stmt = $pdo->prepare("SELECT slug, name, technology, surface_m2, price_eur, rating, badge, verdict, image_url
                               FROM products
                               WHERE status = 'published' AND target_use = ? AND id <> ?
                               ORDER BY rank_global ASC, rating DESC LIMIT 3");
        $stmt->execute([$p['target_use'], (int)$p['id']]);
        $relatedProducts = $stmt->fetchAll();
    }
    // Articles qui mentionnent le slug du produit dans leur HTML (LIKE sur le lien /go/{slug})
    $stmt2 = $pdo->prepare("SELECT slug, title, description, cluster, reading_time
                            FROM articles
                            WHERE status = 'published' AND content_html LIKE ?
                            ORDER BY publish_at DESC LIMIT 4");
    $stmt2->execute(['%/go/' . $p['slug'] . '%']);
    $relatedArticles = $stmt2->fetchAll();
} catch (Throwable $e) {}

function tech_label_t(string $t): string {
    return [
        'co2' => 'CO2 / Propane',
        'uv' => 'UV LED',
        'propane' => 'Propane',
        'aspirant' => 'Aspirant',
        'solaire' => 'Solaire',
        'larvaire' => 'Anti-ponte',
        'combine' => 'Combiné',
    ][$t] ?? ucfirst($t);
}
// product_svg_hero() supprimée — désormais Layout::productImage($p, 'hero')

require __DIR__ . '/partials/header.php';
?>

<main>
    <div class="container">
        <div style="padding-top:6rem;">
            <a href="/blog?cluster=test" class="article-back">← Tous les tests</a>
        </div>

        <!-- HERO PRODUIT -->
        <div class="product-hero">
            <div class="product-hero__media">
                <?= Layout::productImage($p, 'hero') ?>
            </div>
            <div>
                <div class="product-hero__rank-bar">
                    <?php if (!empty($p['rank_global'])): ?>
                        <span style="font-family:'Fraunces', serif; font-size:2rem; color:var(--uv); line-height:1;">0<?= (int)$p['rank_global'] ?></span>
                    <?php endif; ?>
                    <?php if (!empty($p['badge'])): ?>
                        <span class="aff-badge"><?= Layout::escape($p['badge']) ?></span>
                    <?php endif; ?>
                    <?php if (!empty($p['brand'])): ?>
                        · <?= Layout::escape((string)$p['brand']) ?>
                    <?php endif; ?>
                </div>

                <h1 class="product-hero__title"><?= Layout::escape((string)$p['name']) ?></h1>

                <?php if (!empty($p['verdict'])): ?>
                    <p class="product-hero__pitch"><strong style="color:var(--cream);">"<?= Layout::escape((string)$p['verdict']) ?>"</strong></p>
                <?php endif; ?>

                <?php if (!empty($p['rating'])): ?>
                    <?php if (!empty($p['rating']) && (float)$p['rating'] > 0): ?>
                        <div class="stars" style="margin-bottom:1.5rem;">
                            <?php
                            $r = (float)$p['rating'];
                            $full = (int)floor($r);
                            $hasHalf = ($r - $full) >= 0.25 && ($r - $full) < 0.75;
                            for ($i = 0; $i < 5; $i++) {
                                $color = ($i < $full || ($i === $full && $hasHalf)) ? 'var(--forest)' : 'rgba(74,44,20,0.22)';
                                echo '<svg class="stars__svg" viewBox="0 0 24 24" fill="' . $color . '"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>';
                            }
                            ?>
                            <span><?= number_format($r, 1, ',', '') ?>/5<?php if (!empty($p['reviews_count'])): ?> <span style="color:var(--muted);font-weight:400;">(<?= number_format((int)$p['reviews_count'], 0, ',', ' ') ?> avis)</span><?php endif; ?></span>
                        </div>
                    <?php else: ?>
                        <div class="stars" style="margin-bottom:1.5rem; color:var(--muted); font-size:0.9rem;">Pas encore d'avis</div>
                    <?php endif; ?>
                <?php endif; ?>

                <div class="product-hero__price-row">
                    <?php if (!empty($p['price_eur'])): ?>
                        <div class="product-hero__price">
                            <div class="lab">Prix moyen</div>
                            <div class="val"><?= number_format((float)$p['price_eur'], 0, ',', ' ') ?> €</div>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($p['surface_m2'])): ?>
                        <div class="product-hero__price">
                            <div class="lab">Surface</div>
                            <div class="val uv"><?= (int)$p['surface_m2'] ?> m²</div>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($p['noise_db']) && (float)$p['noise_db'] > 0): ?>
                        <div class="product-hero__price">
                            <div class="lab">Bruit</div>
                            <div class="val"><?= number_format((float)$p['noise_db'], 1, ',', '') ?> dB</div>
                        </div>
                    <?php endif; ?>
                </div>

                <a class="btn-amazon-lg" href="/go/<?= Layout::escape($p['slug']) ?>?campaign=test-page" rel="noopener sponsored">
                    Voir le prix sur Amazon →
                </a>
                <div style="margin-top:1rem;">
                    <span class="aff-badge">Lien d'affiliation transparent</span>
                </div>
            </div>
        </div>

        <!-- SPECS -->
        <div class="specs-list">
            <div class="specs-list__item">
                <div class="specs-list__lab">Technologie</div>
                <div class="specs-list__val"><?= Layout::escape(tech_label_t((string)$p['technology'])) ?></div>
            </div>
            <?php if (!empty($p['target_use'])): ?>
            <div class="specs-list__item">
                <div class="specs-list__lab">Idéal pour</div>
                <div class="specs-list__val"><?= Layout::escape(ucfirst((string)$p['target_use'])) ?></div>
            </div>
            <?php endif; ?>
            <?php if (!empty($p['autonomy_hours']) && (int)$p['autonomy_hours'] > 0): ?>
            <div class="specs-list__item">
                <div class="specs-list__lab">Autonomie</div>
                <div class="specs-list__val"><?= (int)$p['autonomy_hours'] ?> h</div>
            </div>
            <?php endif; ?>
            <div class="specs-list__item">
                <div class="specs-list__lab">Note utilisateurs</div>
                <div class="specs-list__val">
                    <?php if (!empty($p['rating']) && (float)$p['rating'] > 0): ?>
                        <span class="text-uv"><?= number_format((float)$p['rating'], 1, ',', '') ?></span><span style="color:var(--muted);font-size:0.85rem;">/5</span>
                    <?php else: ?>
                        <span style="color:var(--muted);font-size:0.95rem;">—</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- AMAZON FEATURES (bullets) -->
        <?php
        $features = !empty($p['features']) ? (json_decode((string)$p['features'], true) ?: []) : [];
        if (!empty($features)):
        ?>
            <section style="max-width: 760px; margin: 3rem auto;">
                <h2 class="display" style="font-size: 1.85rem; font-weight: 500; margin-bottom: 1.25rem; color: var(--ink);">Caractéristiques constructeur</h2>
                <ul style="list-style: none; padding: 0; display: flex; flex-direction: column; gap: 0.65rem;">
                    <?php foreach ($features as $f): ?>
                        <li style="display: flex; gap: 0.75rem; align-items: flex-start; line-height: 1.55;">
                            <span style="color: var(--forest); font-weight: 700; flex-shrink: 0;">+</span>
                            <span style="color: #1a2e25;"><?= Layout::escape((string)$f) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </section>
        <?php endif; ?>

        <!-- AMAZON DESCRIPTION -->
        <?php if (!empty($p['description'])): ?>
            <section class="prose" style="margin: 3rem auto; max-width: 760px;">
                <h2>Description complète</h2>
                <p><?= nl2br(Layout::escape((string)$p['description'])) ?></p>
            </section>
        <?php endif; ?>

        <!-- VERDICT EDITORIAL -->
        <?php if (!empty($p['pitch'])): ?>
            <div class="prose" style="margin: 3rem auto; max-width: 760px;">
                <h2>Notre verdict</h2>
                <p><?= nl2br(Layout::escape((string)$p['pitch'])) ?></p>
            </div>
        <?php endif; ?>

        <!-- PROS / CONS -->
        <?php if (!empty($pros) || !empty($cons)): ?>
        <section style="display:grid; grid-template-columns:1fr; gap:1px; background:var(--line); margin: 3rem 0; border-radius:12px; overflow:hidden;" class="pros-cons-grid">
            <?php if (!empty($pros)): ?>
                <div style="background:var(--ink); padding:2rem;">
                    <div class="mono" style="font-size:0.7rem;color:var(--uv);letter-spacing:0.12em;margin-bottom:1.5rem;">CE QU'ON A AIMÉ</div>
                    <ul style="list-style:none; padding:0; display:flex; flex-direction:column; gap:0.75rem;">
                        <?php foreach ($pros as $pro): ?>
                            <li style="display:flex; gap:0.75rem; align-items:flex-start;">
                                <span style="color:var(--uv); font-weight:700; flex-shrink:0;">+</span>
                                <span style="color:#c8cdd9;"><?= Layout::escape((string)$pro) ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            <?php if (!empty($cons)): ?>
                <div style="background:var(--ink); padding:2rem;">
                    <div class="mono" style="font-size:0.7rem;color:var(--warm);letter-spacing:0.12em;margin-bottom:1.5rem;">CE QU'ON A MOINS AIMÉ</div>
                    <ul style="list-style:none; padding:0; display:flex; flex-direction:column; gap:0.75rem;">
                        <?php foreach ($cons as $con): ?>
                            <li style="display:flex; gap:0.75rem; align-items:flex-start;">
                                <span style="color:var(--warm); font-weight:700; flex-shrink:0;">−</span>
                                <span style="color:#c8cdd9;"><?= Layout::escape((string)$con) ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </section>
        <style>@media (min-width:720px){.pros-cons-grid{grid-template-columns:1fr 1fr !important;}}</style>
        <?php endif; ?>

        <!-- CTA AMAZON FINAL -->
        <div style="text-align:center; padding: 3rem 0 4rem;">
            <a class="btn-amazon-lg" href="/go/<?= Layout::escape($p['slug']) ?>?campaign=test-page-bottom" rel="noopener sponsored">
                Acheter sur Amazon →
            </a>
            <p style="color:var(--muted); margin-top:1.5rem; font-size:0.85rem;">
                Lien d'affiliation : achat sans surcoût pour toi, commission pour nous. Notre analyse s'appuie sur les retours utilisateurs et les specs constructeurs.
            </p>
        </div>

        <!-- MAILLAGE : produits comparables -->
        <?php if (!empty($relatedProducts)): ?>
        <section style="padding: 3rem 0; border-top: 1px solid var(--line);">
            <div class="section__eyebrow">Comparé à — autres cafetières à grain pour <?= Layout::escape((string)$p['target_use']) ?></div>
            <h2 class="section__title" style="font-size: clamp(1.6rem, 3.5vw, 2.5rem); margin-bottom: 2rem;">Les modèles concurrents.</h2>
            <div class="product-grid">
                <?php foreach ($relatedProducts as $rp): ?>
                    <article class="product-card">
                        <div class="product-card__media" style="aspect-ratio:16/9;">
                            <?= Layout::productImage($rp, 'card') ?>
                        </div>
                        <div class="product-card__body">
                            <div class="product-card__cat"><?= Layout::escape(tech_label_t((string)$rp['technology'])) ?></div>
                            <h3 class="product-card__name"><?= Layout::escape((string)$rp['name']) ?></h3>
                            <p class="product-card__verdict">"<?= Layout::escape((string)$rp['verdict']) ?>"</p>
                            <div class="product-card__specs">
                                <div class="product-card__spec"><div class="lab">Prix</div><div class="val"><?= number_format((float)$rp['price_eur'], 0, ',', ' ') ?> €</div></div>
                                <div class="product-card__spec"><div class="lab">Note</div><div class="val">
                                    <?php if (!empty($rp['rating']) && (float)$rp['rating'] > 0): ?>
                                        <span class="uv"><?= number_format((float)$rp['rating'], 1, ',', '') ?></span><span style="font-size:0.85rem;color:var(--muted);">/5</span>
                                    <?php else: ?>
                                        <span style="color:var(--muted);">—</span>
                                    <?php endif; ?>
                                </div></div>
                            </div>
                        </div>
                        <div class="product-card__cta">
                            <a class="btn-amazon" href="/go/<?= Layout::escape($rp['slug']) ?>?campaign=test-related-<?= Layout::escape($p['slug']) ?>" rel="noopener sponsored">Voir Amazon</a>
                            <a class="btn-detail" href="/tests/<?= Layout::escape($rp['slug']) ?>">Test</a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <!-- MAILLAGE : articles qui citent ce produit -->
        <?php if (!empty($relatedArticles)): ?>
        <section style="padding: 3rem 0 5rem; border-top: 1px solid var(--line);">
            <div class="section__eyebrow">À lire aussi — articles qui détaillent ce piège</div>
            <h2 class="section__title" style="font-size: clamp(1.6rem, 3.5vw, 2.5rem); margin-bottom: 2rem;">Les guides liés.</h2>
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
// JSON-LD Product + Review + BreadcrumbList
$baseUrl = rtrim($cfg['base_url'] ?? 'https://cafetiereagrain.fr', '/');
$ld = [
    '@context' => 'https://schema.org',
    '@graph' => [
        [
            '@type' => 'Product',
            'name'  => $p['name'],
            'description' => $p['verdict'] ?? '',
            'brand' => ['@type' => 'Brand', 'name' => $p['brand'] ?? 'Generic'],
            'category' => 'Cafetière à grains',
            'aggregateRating' => [
                '@type' => 'AggregateRating',
                'ratingValue' => (float)$p['rating'],
                'bestRating'  => 10,
                'reviewCount' => 1,
            ],
            'review' => [
                '@type' => 'Review',
                'reviewRating' => ['@type' => 'Rating', 'ratingValue' => (float)$p['rating'], 'bestRating' => 10],
                'author' => ['@type' => 'Organization', 'name' => 'cafetiereagrain.fr'],
                'reviewBody' => $p['pitch'] ?? $p['verdict'] ?? '',
            ],
            'offers' => [
                '@type' => 'Offer',
                'price' => (float)$p['price_eur'],
                'priceCurrency' => 'EUR',
                'availability' => 'https://schema.org/InStock',
                'url' => $canonical,
                'seller' => ['@type' => 'Organization', 'name' => 'Amazon.fr'],
            ],
        ],
        [
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => 'Accueil', 'item' => $baseUrl . '/'],
                ['@type' => 'ListItem', 'position' => 2, 'name' => 'Tests', 'item' => $baseUrl . '/blog?cluster=test'],
                ['@type' => 'ListItem', 'position' => 3, 'name' => $p['name'], 'item' => $canonical],
            ],
        ],
    ],
];
echo "\n<script type=\"application/ld+json\">" . json_encode($ld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "</script>\n";
?>

<?php require __DIR__ . '/partials/footer.php';
