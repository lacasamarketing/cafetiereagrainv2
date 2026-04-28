<?php
// Homepage cafetiereagrain.fr - orientee comparateur ecommerce affiliation
// Sections : hero, top 3 produits, choix par usage, methode, comparateur global, articles, newsletter.

declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

use App\Core\Database;
use App\Core\Layout;
use App\Core\Cron;
use App\Core\AmazonSync;
use App\Core\ArticleGenerator;

// Routine auto-déclenchée : sync Amazon via Rainforest 1×/semaine
Cron::register('amazon_sync', 7 * 86400, function () {
    try {
        (new AmazonSync())->run();
    } catch (\Throwable $e) {
        error_log('amazon_sync routine failed: ' . $e->getMessage());
    }
});

// Routine auto-déclenchée : 1 article par jour via Claude API (stratégie éditoriale 7 jours)
Cron::register('article_daily', 86400, function () {
    try {
        (new ArticleGenerator())->generateFromQueue();
    } catch (\Throwable $e) {
        error_log('article_daily routine failed: ' . $e->getMessage());
    }
});

register_shutdown_function([Cron::class, 'tick']);

$cfg = Layout::loadConfig();

// Top 3 produits du moment (rank_global)
try {
    $pdo = Database::pdo();
    // Top 3 = uniquement les produits réellement bien notés (>= 4/5).
    // Si pas assez de produits qualifiés, on en affiche moins (intégrité éditoriale).
    $top3 = $pdo->query(
        "SELECT slug, name, brand, technology, surface_m2, price_eur, rating, badge, verdict, target_use, image_url, reviews_count
         FROM products
         WHERE status = 'published' AND rating IS NOT NULL AND rating >= 4
         ORDER BY rating DESC, reviews_count DESC
         LIMIT 3"
    )->fetchAll();
} catch (Throwable $e) {
    $top3 = [];
}

// Tableau comparatif global (6 produits)
try {
    $compareList = $pdo->query(
        "SELECT slug, name, technology, surface_m2, price_eur, rating, target_use, verdict, image_url
         FROM products
         WHERE status = 'published' AND rank_global IS NOT NULL
         ORDER BY rank_global ASC
         LIMIT 6"
    )->fetchAll();
} catch (Throwable $e) {
    $compareList = [];
}

// Derniers articles
try {
    $featured = $pdo->query(
        "SELECT slug, title, description, cluster, reading_time
         FROM articles
         WHERE status = 'published'
         ORDER BY publish_at DESC, created_at DESC
         LIMIT 4"
    )->fetchAll();
} catch (Throwable $e) {
    $featured = [];
}

// Compteurs honnêtes (dynamiques) pour le hero
try {
    $countProducts = (int)$pdo->query("SELECT COUNT(*) FROM products WHERE status = 'published'")->fetchColumn();
} catch (Throwable $e) { $countProducts = 0; }
try {
    $countArticles = (int)$pdo->query("SELECT COUNT(*) FROM articles WHERE status = 'published'")->fetchColumn();
} catch (Throwable $e) { $countArticles = 0; }

$pageTitle = 'Cafetière à grains 2026 : le comparatif indépendant';
$pageDescription = 'Comparatif indépendant des cafetières à grain (UV, CO2, propane, anti-tigre). Retours utilisateurs croisés avec les specs constructeurs, verdict par usage et par budget.';

require __DIR__ . '/partials/header.php';

// ----------- helpers locaux -----------
function tech_label(string $t): string {
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
function stars_svg(float $rating): string {
    // rating est sur 5 (standard Amazon)
    $full = (int)floor($rating);
    $hasHalf = ($rating - $full) >= 0.25 && ($rating - $full) < 0.75;
    $out = '';
    for ($i = 0; $i < 5; $i++) {
        if ($i < $full) {
            $color = 'var(--forest)';
        } elseif ($i === $full && $hasHalf) {
            $color = 'var(--forest)';
        } else {
            $color = 'rgba(74,44,20,0.22)';
        }
        $out .= '<svg class="stars__svg" viewBox="0 0 24 24" fill="' . $color . '" xmlns="http://www.w3.org/2000/svg"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>';
    }
    return $out;
}
// product_svg_placeholder() supprimée — désormais Layout::productImage() / Layout::productSvgPlaceholder()
?>

<main>
    <!-- ============ HERO ============ -->
    <section class="hero">
        <div class="container hero__grid">
            <div>
                <div class="hero__eyebrow">ÉDITION ÉTÉ 2026 · COMPARATEUR INDÉPENDANT</div>
                <h1 class="hero__title">
                    Piège à<br>
                    <span class="it">moustiques :</span><br>
                    <span class="uv">le verdict.</span>
                </h1>
                <p class="hero__lede">
                    Le comparatif obsessionnel des cafetières à grain (UV, CO2, propane, anti-tigre).
                    Sélection ouverte, retours utilisateurs croisés avec les specs constructeurs.
                    <strong style="color:var(--ink);">Dormez en paix dehors.</strong>
                </p>

                <div class="hero__stats">
                    <div class="hero__stat">
                        <div class="num display"><?= $countProducts ?: '—' ?></div>
                        <div class="lab">modèles analysés</div>
                    </div>
                    <div class="hero__stat">
                        <div class="num display">8</div>
                        <div class="lab">critères croisés</div>
                    </div>
                    <div class="hero__stat">
                        <div class="num display">0</div>
                        <div class="lab">publi déguisée</div>
                    </div>
                </div>

                <div class="hero__cta">
                    <a href="#top3" class="btn btn--primary">Voir le top 3 →</a>
                    <a href="/blog?cluster=guide" class="text-link">Nos guides d'achat</a>
                </div>
                <div style="margin-top:2rem;">
                    <span class="aff-badge">Membre Amazon Partenaires France</span>
                </div>
            </div>

            <div class="trap-canvas-wrap">
                <svg viewBox="0 0 480 480" xmlns="http://www.w3.org/2000/svg" aria-label="Illustration animée d'un cafetière à grains">
                    <!-- Halo lime de fond -->
                    <circle cx="240" cy="240" r="200" fill="#c6e870" opacity="0.4"/>
                    <circle cx="240" cy="240" r="200" fill="none" stroke="#1f5742" stroke-width="1" opacity="0.18"/>

                    <!-- Cercles concentriques -->
                    <circle cx="240" cy="240" r="148" fill="none" stroke="#1f5742" stroke-width="1" opacity="0.25"/>
                    <circle class="pam-halo" cx="240" cy="240" r="100" fill="none" stroke="#1f5742" stroke-width="1.5" opacity="0.55" style="transform-origin:240px 240px;"/>

                    <!-- Centre blanc -->
                    <circle cx="240" cy="240" r="78" fill="#fff"/>

                    <!-- Aiguille rotative -->
                    <g class="pam-rotate-slow" style="transform-origin:240px 240px;">
                        <line x1="240" y1="240" x2="240" y2="170" stroke="#1f5742" stroke-width="2.5" stroke-linecap="round" opacity="0.55"/>
                        <circle cx="240" cy="170" r="4" fill="#1f5742" opacity="0.7"/>
                    </g>

                    <!-- Goutte/feuille au centre -->
                    <g class="pam-leaf-a" style="transform-origin:240px 280px;">
                        <path d="M 240 196 C 218 220, 218 268, 240 290 C 262 268, 262 220, 240 196 Z" fill="#1f5742"/>
                    </g>

                    <!-- Cœur lime pulsant -->
                    <g class="pam-dot" style="transform-origin:240px 240px;">
                        <circle cx="240" cy="240" r="14" fill="#c6e870"/>
                        <circle cx="240" cy="240" r="6" fill="#1f5742"/>
                    </g>

                    <!-- Moustiques en orbite -->
                    <g class="pam-orbit-a" style="transform-origin:240px 240px;">
                        <circle cx="380" cy="240" r="3" fill="#0a1f17"/>
                    </g>
                    <g class="pam-orbit-b" style="transform-origin:240px 240px;">
                        <circle cx="120" cy="240" r="2.5" fill="#0a1f17" opacity="0.7"/>
                    </g>

                    <!-- Feuilles flottantes -->
                    <g class="pam-leaf-a" style="transform-origin:80px 420px;">
                        <path d="M 80 408 C 70 396, 70 380, 80 372 C 90 380, 90 396, 80 408 Z" fill="#1f5742" opacity="0.75"/>
                    </g>
                    <g class="pam-leaf-b" style="transform-origin:400px 80px;">
                        <path d="M 400 92 C 392 84, 392 70, 400 64 C 408 70, 408 84, 400 92 Z" fill="#1f5742" opacity="0.65"/>
                    </g>
                </svg>
            </div>
        </div>
    </section>

    <!-- ============ MARQUEE EXPERTISES ============ -->
    <div class="marquee">
        <div class="marquee-track" data-speed="1" data-speed-mobile="1.6">
            <?php
            // Alternance : mot style A (Inter Black noir) / icône / mot style B (Fraunces italic vert)
            // Icônes : 0=moustique · 1=goutte · 2=loupe · 3=étoile · 4=cible
            $marqueeItems = [
                ['Anti-tigre',                      'a'], ['icon', 0],
                ['Sélection ouverte',               'b'], ['icon', 2],
                ['Comparateur indépendant',         'a'], ['icon', 4],
                ['Retours utilisateurs croisés',    'b'], ['icon', 3],
                ['UV · CO2 · propane',              'a'], ['icon', 0],
                ['Recommandation par usage',        'b'], ['icon', 1],
                ['Verdict argumenté',               'a'], ['icon', 3],
                ['Croisé avec les specs',           'b'], ['icon', 2],
                ['UV · CO2 · propane · anti-tigre', 'a'], ['icon', 4],
                ['Édition été 2026',                'b'], ['icon', 1],
            ];
            $marqueeItems = array_merge($marqueeItems, $marqueeItems); // boucle infinie
            foreach ($marqueeItems as $it):
                if ($it[0] === 'icon'):
                    $idx = (int) $it[1];
                    ?><span class="m-icon m-icon-<?= $idx ?>" aria-hidden="true"><?php
                    if ($idx === 0): // moustique stylisé ?>
                        <svg viewBox="0 0 60 60" xmlns="http://www.w3.org/2000/svg">
                            <ellipse cx="30" cy="32" rx="6" ry="11" fill="#1f5742"/>
                            <line x1="30" y1="22" x2="30" y2="14" stroke="#1f5742" stroke-width="2.2" stroke-linecap="round"/>
                            <ellipse cx="20" cy="22" rx="11" ry="3" fill="#c6e870" opacity="0.85"/>
                            <ellipse cx="40" cy="22" rx="11" ry="3" fill="#c6e870" opacity="0.85"/>
                            <line x1="28" y1="14" x2="24" y2="8" stroke="#1f5742" stroke-width="1.6" stroke-linecap="round"/>
                            <line x1="32" y1="14" x2="36" y2="8" stroke="#1f5742" stroke-width="1.6" stroke-linecap="round"/>
                            <line x1="30" y1="43" x2="22" y2="50" stroke="#1f5742" stroke-width="1.8" stroke-linecap="round"/>
                            <line x1="30" y1="43" x2="38" y2="50" stroke="#1f5742" stroke-width="1.8" stroke-linecap="round"/>
                        </svg>
                    <?php elseif ($idx === 1): // goutte / feuille (rappel logo) ?>
                        <svg viewBox="0 0 60 60" xmlns="http://www.w3.org/2000/svg">
                            <circle cx="30" cy="30" r="22" fill="none" stroke="#1f5742" stroke-width="2"/>
                            <path d="M 30 14 C 22 22, 22 38, 30 46 C 38 38, 38 22, 30 14 Z" fill="#1f5742"/>
                            <circle cx="30" cy="30" r="3.5" fill="#c6e870"/>
                        </svg>
                    <?php elseif ($idx === 2): // loupe ?>
                        <svg viewBox="0 0 60 60" xmlns="http://www.w3.org/2000/svg">
                            <circle cx="24" cy="24" r="14" fill="#c6e870" stroke="#1f5742" stroke-width="3"/>
                            <line x1="34" y1="34" x2="50" y2="50" stroke="#0a1f17" stroke-width="4" stroke-linecap="round"/>
                            <circle cx="24" cy="24" r="4" fill="#1f5742"/>
                        </svg>
                    <?php elseif ($idx === 3): // étoile ?>
                        <svg viewBox="0 0 60 60" xmlns="http://www.w3.org/2000/svg">
                            <polygon points="30,4 36.5,21 54,22 40,33 45,50 30,40 15,50 20,33 6,22 23.5,21" fill="#c6e870" stroke="#1f5742" stroke-width="2.5" stroke-linejoin="round"/>
                            <circle cx="30" cy="28" r="3" fill="#1f5742"/>
                        </svg>
                    <?php else: // cible ?>
                        <svg viewBox="0 0 60 60" xmlns="http://www.w3.org/2000/svg">
                            <circle cx="30" cy="30" r="24" fill="none" stroke="#1f5742" stroke-width="2.5"/>
                            <circle cx="30" cy="30" r="14" fill="none" stroke="#1f5742" stroke-width="2.5"/>
                            <circle cx="30" cy="30" r="6" fill="#c6e870" stroke="#1f5742" stroke-width="2.5"/>
                            <line x1="6" y1="30" x2="14" y2="30" stroke="#1f5742" stroke-width="2.2" stroke-linecap="round"/>
                            <line x1="46" y1="30" x2="54" y2="30" stroke="#1f5742" stroke-width="2.2" stroke-linecap="round"/>
                            <line x1="30" y1="6" x2="30" y2="14" stroke="#1f5742" stroke-width="2.2" stroke-linecap="round"/>
                            <line x1="30" y1="46" x2="30" y2="54" stroke="#1f5742" stroke-width="2.2" stroke-linecap="round"/>
                        </svg>
                    <?php endif; ?>
                    </span><?php
                else:
                    [$word, $style] = $it;
                    ?><span class="m-word m-word-<?= Layout::escape((string)$style) ?>"><?= Layout::escape((string)$word) ?></span><?php
                endif;
            endforeach;
            ?>
        </div>
    </div>

    <!-- ============ TOP / MIEUX NOTÉS ============ -->
    <section id="top3" class="section">
        <div class="container">
            <div style="display:flex; align-items:end; justify-content:space-between; flex-wrap:wrap; gap:1.5rem; margin-bottom:1rem;">
                <div>
                    <div class="section__eyebrow">/01 — LES MIEUX NOTÉS</div>
                    <h2 class="section__title"><?= count($top3) ?> piège<?= count($top3) > 1 ? 's' : '' ?> <span class="it">qui convainquent</span> les utilisateurs.</h2>
                </div>
                <div class="mono" style="font-size:0.7rem; color:var(--muted); letter-spacing:0.12em;">MIS À JOUR LE <?= date('d.m.Y') ?></div>
            </div>

            <?php if (empty($top3)): ?>
                <p class="text-muted" style="margin-top:2rem;">Aucun produit avec une note suffisante n'est encore référencé. Ajoute-en via l'admin produits.</p>
            <?php else: ?>
                <div class="product-grid">
                    <?php foreach ($top3 as $i => $p): ?>
                        <article class="product-card">
                            <?php if (!empty($p['badge'])): ?>
                                <div class="product-card__badge <?= $i === 0 ? 'product-card__badge--uv' : '' ?>"><?= Layout::escape($p['badge']) ?></div>
                            <?php endif; ?>
                            <div class="product-card__rank">0<?= $i + 1 ?></div>

                            <div class="product-card__media">
                                <?= Layout::productImage($p, 'card') ?>
                            </div>

                            <div class="product-card__body">
                                <div class="product-card__cat"><?= Layout::escape(tech_label((string)$p['technology'])) ?> · <?= (int)$p['surface_m2'] ?> m²</div>
                                <h3 class="product-card__name"><?= Layout::escape($p['name']) ?></h3>
                                <p class="product-card__verdict">"<?= Layout::escape($p['verdict']) ?>"</p>

                                <?php if (!empty($p['rating']) && (float)$p['rating'] > 0): ?>
                                    <div class="stars" style="margin-bottom:1rem;">
                                        <?= stars_svg((float)$p['rating']) ?>
                                        <span><?= number_format((float)$p['rating'], 1, ',', '') ?>/5</span>
                                    </div>
                                <?php else: ?>
                                    <div class="stars" style="margin-bottom:1rem; color:var(--muted); font-size:0.8rem;">Pas encore noté</div>
                                <?php endif; ?>

                                <div class="product-card__specs">
                                    <div class="product-card__spec">
                                        <div class="lab">Prix moyen</div>
                                        <div class="val"><?= number_format((float)$p['price_eur'], 0, ',', ' ') ?> €</div>
                                    </div>
                                    <div class="product-card__spec">
                                        <div class="lab">Note</div>
                                        <div class="val">
                                            <?php if (!empty($p['rating']) && (float)$p['rating'] > 0): ?>
                                                <span class="uv"><?= number_format((float)$p['rating'], 1, ',', '') ?></span><span style="font-size:0.85rem; color:var(--muted);">/5</span>
                                            <?php else: ?>
                                                <span style="font-size:0.95rem; color:var(--muted);">—</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="product-card__cta">
                                <a class="btn-amazon" href="/go/<?= Layout::escape($p['slug']) ?>?campaign=home-top3" rel="noopener sponsored">Voir sur Amazon →</a>
                                <a class="btn-detail" href="/tests/<?= Layout::escape($p['slug']) ?>">Test</a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- ============ CHOISIR PAR USAGE ============ -->
    <section class="section">
        <div class="container">
            <div class="section__eyebrow">/02 — CHOISIR PAR USAGE</div>
            <h2 class="section__title">Pour <span class="it">quel</span> espace ?</h2>

            <div class="usage-grid">
                <a class="usage-tile" href="/blog?cluster=comparatif">
                    <div class="usage-tile__icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18M5 21V8l7-5 7 5v13M9 21v-7h6v7M9 14h6"/></svg>
                    </div>
                    <h3 class="usage-tile__title">Pour ta terrasse</h3>
                    <p class="usage-tile__desc">CO2 ou propane. Surface 100 à 800 m². Notre top 3 pour les soirées dehors sans corvée.</p>
                    <div class="usage-tile__arrow">VOIR LE GUIDE →</div>
                </a>
                <a class="usage-tile" href="/blog?cluster=test">
                    <div class="usage-tile__icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7h18v3H3zM4 10v10a1 1 0 001 1h14a1 1 0 001-1V10M9 14h6"/></svg>
                    </div>
                    <h3 class="usage-tile__title">Pour ta chambre</h3>
                    <p class="usage-tile__desc">Silencieux, sans grille électrique grésillante. Notre sélection des modèles vraiment compatibles avec le sommeil.</p>
                    <div class="usage-tile__arrow">VOIR LE GUIDE →</div>
                </a>
                <a class="usage-tile" href="/blog?cluster=guide">
                    <div class="usage-tile__icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/></svg>
                    </div>
                    <h3 class="usage-tile__title">Anti-tigre (Aedes)</h3>
                    <p class="usage-tile__desc">Les seuls modèles vraiment efficaces contre <em>Aedes albopictus</em>. CO2, attractants chimiques, pièges à ponte.</p>
                    <div class="usage-tile__arrow">VOIR LE GUIDE →</div>
                </a>
                <a class="usage-tile" href="/blog?cluster=guide">
                    <div class="usage-tile__icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12h18M3 6h18M3 18h18"/></svg>
                    </div>
                    <h3 class="usage-tile__title">Pour les pros</h3>
                    <p class="usage-tile__desc">Restaurants, hôtels, exploitations agricoles. Les solutions qui couvrent grandes surfaces sans alourdir le budget annuel.</p>
                    <div class="usage-tile__arrow">VOIR LE GUIDE →</div>
                </a>
            </div>
        </div>
    </section>

    <!-- ============ COMPARATEUR GLOBAL ============ -->
    <?php if (!empty($compareList)): ?>
    <section class="section">
        <div class="container">
            <div class="section__eyebrow">/03 — LE COMPARATEUR</div>
            <h2 class="section__title">6 modèles, <span class="it">une grille</span>, zéro flou.</h2>

            <div style="overflow-x:auto; margin-top:2rem;">
                <table class="compare-table">
                    <thead>
                        <tr>
                            <th>Modèle</th>
                            <th>Techno</th>
                            <th>Surface</th>
                            <th>Prix</th>
                            <th>Note</th>
                            <th>Verdict</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($compareList as $p): ?>
                            <tr>
                                <td><strong><?= Layout::escape($p['name']) ?></strong></td>
                                <td><?= Layout::escape(tech_label((string)$p['technology'])) ?></td>
                                <td><?= (int)$p['surface_m2'] ?> m²</td>
                                <td><?= number_format((float)$p['price_eur'], 0, ',', ' ') ?> €</td>
                                <td>
                                    <?php if (!empty($p['rating']) && (float)$p['rating'] > 0): ?>
                                        <span class="text-uv"><?= number_format((float)$p['rating'], 1, ',', '') ?></span>/5
                                    <?php else: ?>
                                        <span style="color:var(--muted);">—</span>
                                    <?php endif; ?>
                                </td>
                                <td style="color:var(--muted); font-style:italic;"><?= Layout::escape((string)$p['verdict']) ?></td>
                                <td><a class="btn" href="/go/<?= Layout::escape($p['slug']) ?>?campaign=home-compare">Amazon →</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- ============ MÉTHODE — style MDM ============ -->
    <section class="section process-section" id="methode">
        <div class="container">
            <div class="process-head">
                <div>
                    <div class="section__eyebrow">/04 — MÉTHODE</div>
                    <h2 class="section__title">On compare, on croise,<br><span class="grad">on tranche.</span></h2>
                </div>
                <p class="process-desc">Un protocole d'analyse identique pour chaque modèle : sélection ouverte, synthèse des retours utilisateurs, croisement avec les specs constructeurs, verdict argumenté. C'est ce qui sépare un comparatif de fond d'une publi-rédaction déguisée.</p>
            </div>

            <div class="process-wrap">
                <div class="process-particles" id="processParticles" aria-hidden="true"></div>
                <div class="process-line" aria-hidden="true"></div>
                <div class="process">
                    <div class="process-step">
                        <div class="process-num">01</div>
                        <h4>Sélection ouverte</h4>
                        <p>On recense l'ensemble des modèles disponibles à la vente : best-sellers, marques européennes spécialisées, alternatives confidentielles.</p>
                    </div>
                    <div class="process-step">
                        <div class="process-num">02</div>
                        <h4>Synthèse des retours</h4>
                        <p>Les utilisateurs remontent ce qui marche vraiment et ce qui déçoit. On compile, on recoupe, on identifie les points forts et les défauts récurrents de chaque modèle.</p>
                    </div>
                    <div class="process-step">
                        <div class="process-num">03</div>
                        <h4>Croisement specs</h4>
                        <p>Surface couverte, dB, autonomie, technologie, coût annuel d'usage. Toutes les données constructeurs confrontées aux retours utilisateurs.</p>
                    </div>
                    <div class="process-step">
                        <div class="process-num">04</div>
                        <h4>Verdict argumenté</h4>
                        <p>Score sur 10, coût total sur 5 saisons, recommandation par usage et par budget. Sans complaisance pour les gadgets.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ============ ARTICLES (depuis BDD) ============ -->
    <?php if (!empty($featured)): ?>
    <section class="section">
        <div class="container">
            <div class="section__eyebrow">/05 — À LIRE AVANT D'ACHETER</div>
            <h2 class="section__title">Les <span class="it">décortiquages</span>.</h2>
            <div class="guides">
                <?php foreach ($featured as $a): ?>
                    <a class="guide-card" href="/blog/<?= Layout::escape($a['slug']) ?>">
                        <div class="cat"><?= Layout::escape(strtoupper($a['cluster'] ?? 'GUIDE')) ?></div>
                        <h3 class="h"><?= Layout::escape($a['title']) ?></h3>
                        <p class="d"><?= Layout::escape($a['description']) ?></p>
                        <div class="arr">LIRE L'ARTICLE →</div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>


</main>

<script src="/assets/js/marquee.js" defer></script>
<script>
// Particules flottantes section méthode (génération JS pour pas polluer le PHP)
(function () {
    function spawn() {
        var box = document.getElementById('processParticles');
        if (!box) return;
        var count = window.matchMedia('(max-width: 720px)').matches ? 8 : 18;
        var html = '';
        for (var i = 0; i < count; i++) {
            var x = Math.floor(Math.random() * 100);
            var y = Math.floor(Math.random() * 100);
            var sz = (3 + Math.random() * 7).toFixed(1);
            var dur = (8 + Math.random() * 10).toFixed(1);
            var del = (Math.random() * 8).toFixed(1);
            var mx = (Math.random() * 80 - 40).toFixed(0);
            var my = (Math.random() * 80 - 40).toFixed(0);
            html += '<span style="--x:' + x + '%;--y:' + y + '%;--sz:' + sz + 'px;--dur:' + dur + 's;--del:' + del + 's;--mx:' + mx + 'px;--my:' + my + 'px;"></span>';
        }
        box.innerHTML = html;
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', spawn);
    } else {
        spawn();
    }
})();
</script>

<script src="/assets/js/mosquitoes-floating.js" defer></script>

<?php require __DIR__ . '/partials/footer.php';
