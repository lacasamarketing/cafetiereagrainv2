<?php
// Blog list cafetiereagrain.fr
// URL: /blog ou /blog?cluster=comparatif

declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

use App\Core\Database;
use App\Core\Layout;

// Fix OVH FastCGI : si l'URL est /blog/{slug}, OVH peut router ici avec PATH_INFO
// au lieu d'appliquer le .htaccess qui rewrite vers article.php. On detecte et delegue.
$_slugFromPath = '';
if (!empty($_SERVER['PATH_INFO'])) {
    $_slugFromPath = trim((string)$_SERVER['PATH_INFO'], '/');
}
if ($_slugFromPath === '' && !empty($_SERVER['REQUEST_URI'])) {
    $_path = parse_url((string)$_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '';
    if (preg_match('#^/blog/([a-z0-9\-]+)/?$#i', $_path, $_m)) {
        $_slugFromPath = $_m[1];
    }
}
if ($_slugFromPath !== '' && preg_match('/^[a-z0-9\-]+$/', $_slugFromPath)) {
    $_GET['slug'] = $_slugFromPath;
    require __DIR__ . '/article.php';
    exit;
}

$cfg = Layout::loadConfig();

$cluster = isset($_GET['cluster']) ? preg_replace('/[^a-z\-]/', '', strtolower((string)$_GET['cluster'])) : '';
$validClusters = ['comparatif', 'guide', 'test', 'saison', 'usage', 'diy', 'science', 'sante', 'faq', 'general'];
if ($cluster !== '' && !in_array($cluster, $validClusters, true)) {
    $cluster = '';
}

try {
    $pdo = Database::pdo();
    if ($cluster !== '') {
        $stmt = $pdo->prepare("SELECT slug, title, description, cluster, reading_time, publish_at,
                                       SUBSTRING(content_html, 1, 4000) AS content_excerpt
                               FROM articles WHERE status = 'published' AND cluster = ?
                               ORDER BY publish_at DESC, created_at DESC LIMIT 100");
        $stmt->execute([$cluster]);
    } else {
        $stmt = $pdo->query("SELECT slug, title, description, cluster, reading_time, publish_at,
                                    SUBSTRING(content_html, 1, 4000) AS content_excerpt
                             FROM articles WHERE status = 'published'
                             ORDER BY publish_at DESC, created_at DESC LIMIT 100");
    }
    $articles = $stmt->fetchAll();

    // Détection des produits cités dans chaque article (premier /go/{slug} trouvé)
    $articleProductSlug = [];
    $allFoundSlugs = [];
    foreach ($articles as $i => $a) {
        if (!empty($a['content_excerpt']) && preg_match('#/go/([a-z0-9\-]+)#i', (string)$a['content_excerpt'], $m)) {
            $articleProductSlug[$a['slug']] = $m[1];
            $allFoundSlugs[] = $m[1];
        }
    }
    $allFoundSlugs = array_unique($allFoundSlugs);

    // Bulk fetch des image_url pour tous les slugs produits trouvés
    $productImages = [];
    if (!empty($allFoundSlugs)) {
        $place = implode(',', array_fill(0, count($allFoundSlugs), '?'));
        $stmt2 = $pdo->prepare("SELECT slug, image_url FROM products WHERE status = 'published' AND slug IN ($place)");
        $stmt2->execute($allFoundSlugs);
        foreach ($stmt2->fetchAll() as $p) {
            $productImages[$p['slug']] = $p['image_url'];
        }
    }
} catch (Throwable $e) {
    $articles = [];
    $articleProductSlug = [];
    $productImages = [];
}

// Générateur de SVG thématique par cluster (fallback quand pas d'image produit)
function clusterFallbackSvg(string $cluster): string {
    $palettes = [
        'comparatif' => ['bg' => '#eef5ec', 'stroke' => '#1f5742', 'accent' => '#c6e870'],
        'guide'      => ['bg' => '#f4efe6', 'stroke' => '#1f5742', 'accent' => '#c6e870'],
        'test'       => ['bg' => '#eaf2e8', 'stroke' => '#1f5742', 'accent' => '#c6e870'],
        'saison'     => ['bg' => '#f7f0e0', 'stroke' => '#b87333', 'accent' => '#c6e870'],
        'usage'      => ['bg' => '#eef5ec', 'stroke' => '#1f5742', 'accent' => '#c6e870'],
        'diy'        => ['bg' => '#e8efe5', 'stroke' => '#3a6b3e', 'accent' => '#c6e870'],
        'science'    => ['bg' => '#e8eef4', 'stroke' => '#1f5742', 'accent' => '#c6e870'],
        'sante'      => ['bg' => '#f4e8e8', 'stroke' => '#9b3a3a', 'accent' => '#c6e870'],
        'faq'        => ['bg' => '#f0eef5', 'stroke' => '#1f5742', 'accent' => '#c6e870'],
        'general'    => ['bg' => '#eef5ec', 'stroke' => '#1f5742', 'accent' => '#c6e870'],
    ];
    $p = $palettes[$cluster] ?? $palettes['general'];
    $bg = $p['bg']; $st = $p['stroke']; $ac = $p['accent'];

    // Icône thématique par cluster
    $icons = [
        'comparatif' => '<g><rect x="40" y="80" width="14" height="40" fill="'.$st.'" rx="2"/><rect x="62" y="60" width="14" height="60" fill="'.$st.'" rx="2"/><rect x="84" y="40" width="14" height="80" fill="'.$ac.'" rx="2"/></g>',
        'guide'      => '<g><path d="M 40 50 Q 70 45, 100 50 L 100 110 Q 70 105, 40 110 Z" fill="none" stroke="'.$st.'" stroke-width="2.5"/><path d="M 70 50 L 70 110" stroke="'.$st.'" stroke-width="2"/><circle cx="55" cy="80" r="3" fill="'.$ac.'"/><circle cx="85" cy="80" r="3" fill="'.$ac.'"/></g>',
        'test'       => '<g><circle cx="65" cy="75" r="22" fill="none" stroke="'.$st.'" stroke-width="2.5"/><circle cx="65" cy="75" r="8" fill="'.$ac.'"/><line x1="83" y1="93" x2="105" y2="115" stroke="'.$st.'" stroke-width="3" stroke-linecap="round"/></g>',
        'saison'     => '<g><circle cx="70" cy="80" r="14" fill="'.$ac.'"/><g stroke="'.$st.'" stroke-width="2" stroke-linecap="round"><line x1="70" y1="55" x2="70" y2="48"/><line x1="70" y1="112" x2="70" y2="105"/><line x1="42" y1="80" x2="49" y2="80"/><line x1="91" y1="80" x2="98" y2="80"/><line x1="51" y1="61" x2="56" y2="66"/><line x1="84" y1="94" x2="89" y2="99"/><line x1="51" y1="99" x2="56" y2="94"/><line x1="84" y1="66" x2="89" y2="61"/></g></g>',
        'usage'      => '<g><path d="M 70 45 C 55 45, 50 60, 55 75 L 60 90 L 80 90 L 85 75 C 90 60, 85 45, 70 45 Z" fill="none" stroke="'.$st.'" stroke-width="2.5"/><circle cx="70" cy="65" r="4" fill="'.$ac.'"/><line x1="62" y1="100" x2="78" y2="100" stroke="'.$st.'" stroke-width="2"/><line x1="64" y1="108" x2="76" y2="108" stroke="'.$st.'" stroke-width="2"/></g>',
        'diy'        => '<g><path d="M 50 100 Q 50 70, 70 60 Q 90 70, 90 100 Z" fill="'.$ac.'" stroke="'.$st.'" stroke-width="2"/><line x1="70" y1="60" x2="70" y2="100" stroke="'.$st.'" stroke-width="1.5"/><path d="M 70 75 Q 60 70, 55 78" fill="none" stroke="'.$st.'" stroke-width="1.5"/><path d="M 70 85 Q 80 80, 85 88" fill="none" stroke="'.$st.'" stroke-width="1.5"/></g>',
        'science'    => '<g><ellipse cx="70" cy="80" rx="28" ry="11" fill="none" stroke="'.$st.'" stroke-width="2" transform="rotate(30 70 80)"/><ellipse cx="70" cy="80" rx="28" ry="11" fill="none" stroke="'.$st.'" stroke-width="2" transform="rotate(-30 70 80)"/><ellipse cx="70" cy="80" rx="28" ry="11" fill="none" stroke="'.$st.'" stroke-width="2"/><circle cx="70" cy="80" r="5" fill="'.$ac.'"/></g>',
        'sante'      => '<g><path d="M 70 110 C 50 95, 38 80, 38 65 C 38 55, 46 48, 55 48 C 62 48, 67 52, 70 58 C 73 52, 78 48, 85 48 C 94 48, 102 55, 102 65 C 102 80, 90 95, 70 110 Z" fill="'.$ac.'" stroke="'.$st.'" stroke-width="2"/></g>',
        'faq'        => '<g><circle cx="70" cy="80" r="28" fill="none" stroke="'.$st.'" stroke-width="2.5"/><path d="M 60 70 Q 60 60, 70 60 Q 82 60, 80 72 Q 78 80, 70 84 L 70 90" fill="none" stroke="'.$st.'" stroke-width="2.5" stroke-linecap="round"/><circle cx="70" cy="100" r="2.5" fill="'.$ac.'"/></g>',
        'general'    => '<g><path d="M 70 50 C 55 60, 55 90, 70 100 C 85 90, 85 60, 70 50 Z" fill="'.$ac.'" stroke="'.$st.'" stroke-width="2"/><line x1="70" y1="50" x2="70" y2="100" stroke="'.$st.'" stroke-width="1.5"/></g>',
    ];
    $icon = $icons[$cluster] ?? $icons['general'];
    return '<svg viewBox="0 0 140 160" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid slice"><rect width="140" height="160" fill="'.$bg.'"/>'.$icon.'</svg>';
}

$clusterLabels = [
    'comparatif' => 'Comparatifs',
    'guide'      => 'Guides d\'achat',
    'test'       => 'Tests produits',
    'saison'     => 'Saisonnalité',
    'usage'      => 'Conseils d\'usage',
    'diy'        => 'Remèdes naturels',
    'science'    => 'Biologie & science',
    'sante'      => 'Santé & sécurité',
    'faq'        => 'Questions fréquentes',
    'general'    => 'Articles',
];
$clusterH1 = [
    'comparatif' => 'Comparatifs de cafetières à grain',
    'guide'      => 'Guides d\'achat — Cafetière à grains',
    'test'       => 'Tests de cafetières à grain',
    'saison'     => 'Cafetières à grain par saison',
    'usage'      => 'Conseils d\'usage et installation',
    'diy'        => 'Anti-moustique fait maison & remèdes naturels',
    'science'    => 'Biologie du moustique & science',
    'sante'      => 'Santé, sécurité & moustiques',
    'faq'        => 'Questions fréquentes sur les cafetières à grain',
    'general'    => 'Articles',
];
$clusterDesc = [
    'comparatif' => 'Tous nos comparatifs de cafetières à grain : UV vs CO2, modèles 2026, intérieur/extérieur, anti-tigre. Sélection indépendante et verdicts argumentés.',
    'guide'      => 'Nos guides d\'achat pour choisir le bon cafetière à grains selon ton usage : terrasse, chambre, pro, anti-tigre. Surface, bruit, coût annuel.',
    'test'       => 'Analyses détaillées des cafetières à grain : Mosquito Magnet, Biogents, et les autres modèles du marché. Specs constructeurs croisées avec les retours utilisateurs.',
    'saison'     => 'Quand installer son cafetière à grains, comment l\'utiliser au printemps, en été, et le stocker l\'hiver. Calendrier d\'efficacité.',
    'usage'      => 'Comment installer et exploiter au mieux son cafetière à grains : terrasse, jardin, attractants, propane. Tutoriels et astuces.',
    'diy'        => 'Anti-moustique fait maison : vinaigre blanc, huiles essentielles, plantes répulsives, pièges DIY au CO2. Recettes complètes et ingrédients.',
    'science'    => 'Comprendre le moustique : cycle de vie, mode de reproduction, différences café en grain vs commun, comportement et attraction.',
    'sante'      => 'Santé et moustiques : protection bébés et enfants, allergies aux piqûres, animaux domestiques, maladies vectorielles.',
    'faq'        => 'Réponses aux questions fréquentes sur les cafetières à grain, leur efficacité réelle, leur entretien et leur usage.',
    'general'    => 'Tous les articles sur les cafetières à grain : tests, comparatifs, guides d\'achat, remèdes naturels et conseils d\'usage.',
];
$h1 = $cluster !== '' ? ($clusterH1[$cluster] ?? 'Articles') : 'Tous nos articles sur les cafetières à grain';

$pageTitle = $h1 . ' · Cafetière à grain';
$pageDescription = $cluster !== '' ? ($clusterDesc[$cluster] ?? '') : 'Comparatifs, tests et guides pour choisir un cafetière à grains efficace en 2026. Sélection indépendante, sans publicité déguisée.';
$canonical = ($cfg['base_url'] ?? '') . '/blog' . ($cluster !== '' ? '?cluster=' . $cluster : '');

require __DIR__ . '/partials/header.php';
?>

<main class="article-wrap">
    <div class="container">
        <a href="/" class="article-back">← Accueil</a>
        <h1 class="article-h1"><?= Layout::escape($h1) ?>.</h1>
        <?php if ($cluster !== '' && !empty($clusterDesc[$cluster])): ?>
            <p class="article-lede" style="max-width:760px;"><?= Layout::escape($clusterDesc[$cluster]) ?></p>
        <?php endif; ?>

        <!-- Filtres clusters -->
        <div style="display:flex; flex-wrap:wrap; gap:0.5rem; margin: 2rem 0 1rem;">
            <a href="/blog" class="btn" style="<?= $cluster === '' ? 'border-color:var(--uv); color:var(--uv);' : '' ?>">Tout</a>
            <?php foreach ($clusterLabels as $key => $label): if ($key === 'general') continue; ?>
                <a href="/blog?cluster=<?= $key ?>" class="btn" style="<?= $cluster === $key ? 'border-color:var(--uv); color:var(--uv);' : '' ?>"><?= Layout::escape($label) ?></a>
            <?php endforeach; ?>
        </div>

        <?php if (empty($articles)): ?>
            <p class="text-muted" style="margin-top:3rem;">Aucun article publié pour l'instant. Les premiers comparatifs arrivent.</p>
        <?php else: ?>
            <div class="blog-list blog-list--with-thumbs">
                <?php foreach ($articles as $a):
                    // Détermine la vignette : produit cité (si image) ou SVG thématique
                    $thumbProductSlug = $articleProductSlug[$a['slug']] ?? null;
                    $thumbImageUrl = $thumbProductSlug && !empty($productImages[$thumbProductSlug])
                        ? $productImages[$thumbProductSlug]
                        : null;
                ?>
                    <a class="blog-item blog-item--thumb" href="/blog/<?= Layout::escape($a['slug']) ?>">
                        <div class="blog-item__thumb">
                            <?php if ($thumbImageUrl): ?>
                                <img src="<?= Layout::escape((string)$thumbImageUrl) ?>"
                                     alt="<?= Layout::escape($a['title']) ?>"
                                     loading="lazy" decoding="async">
                            <?php else: ?>
                                <?= clusterFallbackSvg((string)($a['cluster'] ?? 'general')) ?>
                            <?php endif; ?>
                        </div>
                        <div class="blog-item__body">
                            <div class="cat"><?= Layout::escape(strtoupper($a['cluster'] ?? 'GUIDE')) ?></div>
                            <h2 class="h"><?= Layout::escape($a['title']) ?></h2>
                            <p class="d"><?= Layout::escape($a['description']) ?></p>
                            <div class="meta">
                                <?= !empty($a['publish_at']) ? date('d.m.Y', strtotime((string)$a['publish_at'])) : '' ?>
                                <?php if (!empty($a['reading_time'])): ?>
                                    · <?= (int)$a['reading_time'] ?> min de lecture
                                <?php endif; ?>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
            <style>
            .blog-list--with-thumbs { display: flex; flex-direction: column; gap: 14px; margin-top: 2rem; }
            .blog-item--thumb {
                display: grid; grid-template-columns: 140px 1fr; gap: 24px;
                padding: 18px; background: #fff;
                border: 1px solid rgba(74,44,20,.1); border-radius: 16px;
                text-decoration: none; color: inherit;
                transition: transform .2s, box-shadow .2s, border-color .2s;
                align-items: center;
            }
            .blog-item--thumb:hover { transform: translateY(-2px); box-shadow: 0 12px 32px rgba(74,44,20,.08); border-color: rgba(74,44,20,.25); }
            .blog-item__thumb {
                width: 140px; height: 160px; border-radius: 12px;
                overflow: hidden; background: #f5f8f0; flex-shrink: 0;
                display: flex; align-items: center; justify-content: center;
            }
            .blog-item__thumb img { width: 100%; height: 100%; object-fit: contain; padding: 14px; box-sizing: border-box; }
            .blog-item__thumb svg { width: 100%; height: 100%; display: block; }
            .blog-item__body { min-width: 0; }
            .blog-item__body .cat { font-family: 'Inter', sans-serif; font-size: 11px; letter-spacing: .15em; color: var(--forest, #1f5742); font-weight: 500; margin-bottom: 6px; }
            .blog-item__body .h { font-family: 'Fraunces', serif; font-size: clamp(18px, 2vw, 22px); color: var(--forest, #1f5742); font-weight: 500; line-height: 1.25; margin: 0 0 6px; }
            .blog-item__body .d { color: #5b6660; font-size: 14px; line-height: 1.5; margin: 0 0 10px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
            .blog-item__body .meta { font-family: 'Inter', sans-serif; font-size: 12px; color: #888; letter-spacing: .03em; }
            @media (max-width: 640px) {
                .blog-item--thumb { grid-template-columns: 92px 1fr; gap: 14px; padding: 12px; }
                .blog-item__thumb { width: 92px; height: 110px; }
                .blog-item__thumb img { padding: 8px; }
                .blog-item__body .h { font-size: 16px; }
                .blog-item__body .d { font-size: 13px; }
            }
            </style>
        <?php endif; ?>
    </div>
</main>

<?php require __DIR__ . '/partials/footer.php';
