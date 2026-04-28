<?php
// Header public partagé par toutes les pages frontend.
// Charge la charte CSS + meta SEO de base. Les meta-spécifiques sont surchargés
// dans chaque page via les variables $pageTitle, $pageDescription, $canonical.

declare(strict_types=1);

use App\Core\Layout;

$cfg = $cfg ?? Layout::loadConfig();
$baseUrl = $cfg['base_url'] ?? 'https://cafetiereagrain.fr';
$pageTitle = $pageTitle ?? 'Cafetière à grains — Le comparatif obsessionnel 2026';
$pageDescription = $pageDescription ?? 'Le comparatif indépendant des cafetières à grain. Retours utilisateurs croisés avec les specs constructeurs, sans publicité déguisée.';
$canonical = $canonical ?? $baseUrl . ($_SERVER['REQUEST_URI'] ?? '/');
$ogImage = $ogImage ?? $baseUrl . '/assets/img/og-default.png';
$ga4Id = $cfg['analytics']['ga4_id'] ?? '';
$plausibleDomain = $cfg['analytics']['plausible_domain'] ?? '';
?><!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5">
<meta name="theme-color" content="#050810">
<title><?= Layout::escape($pageTitle) ?></title>
<meta name="description" content="<?= Layout::escape($pageDescription) ?>">
<link rel="canonical" href="<?= Layout::escape($canonical) ?>">

<!-- Open Graph -->
<meta property="og:type" content="website">
<meta property="og:locale" content="fr_FR">
<meta property="og:title" content="<?= Layout::escape($pageTitle) ?>">
<meta property="og:description" content="<?= Layout::escape($pageDescription) ?>">
<meta property="og:url" content="<?= Layout::escape($canonical) ?>">
<meta property="og:image" content="<?= Layout::escape($ogImage) ?>">
<meta property="og:site_name" content="cafetiereagrain.fr">

<!-- Twitter -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= Layout::escape($pageTitle) ?>">
<meta name="twitter:description" content="<?= Layout::escape($pageDescription) ?>">
<meta name="twitter:image" content="<?= Layout::escape($ogImage) ?>">

<link rel="icon" type="image/svg+xml" href="/assets/img/favicon.svg">
<link rel="icon" type="image/png" sizes="32x32" href="/assets/img/favicon-32.png">
<link rel="apple-touch-icon" sizes="180x180" href="/assets/img/apple-touch-icon.png">
<link rel="alternate icon" href="/favicon.ico">

<link rel="stylesheet" href="/assets/css/custom.css">
<link rel="stylesheet" href="/assets/css/cafetiere-extras.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;700;900&display=swap" rel="stylesheet">

<!-- JSON-LD : Organization + WebSite + SearchAction (toutes pages) -->
<script type="application/ld+json"><?php echo json_encode([
    '@context' => 'https://schema.org',
    '@graph' => [
        [
            '@type' => 'Organization',
            '@id'   => $baseUrl . '/#organization',
            'name'  => 'Cafetière à grain',
            'url'   => $baseUrl,
            'logo'  => $baseUrl . '/assets/img/logo.svg',
            'sameAs' => [],
            'description' => 'Comparateur indépendant de cafetières à grain : 47 modèles testés, sans publicité déguisée.',
            'foundingDate' => '2026',
            'email' => 'bonjour@lacasamarketing.fr',
        ],
        [
            '@type' => 'WebSite',
            '@id'   => $baseUrl . '/#website',
            'url'   => $baseUrl,
            'name'  => 'Cafetière à grain',
            'inLanguage' => 'fr-FR',
            'publisher' => ['@id' => $baseUrl . '/#organization'],
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => [
                    '@type' => 'EntryPoint',
                    'urlTemplate' => $baseUrl . '/blog?q={search_term_string}',
                ],
                'query-input' => 'required name=search_term_string',
            ],
        ],
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?></script>

<?php if ($plausibleDomain !== ''): ?>
<script defer data-domain="<?= Layout::escape($plausibleDomain) ?>" src="https://plausible.io/js/script.js"></script>
<?php endif; ?>
<?php if ($ga4Id !== ''): ?>
<script async src="https://www.googletagmanager.com/gtag/js?id=<?= Layout::escape($ga4Id) ?>"></script>
<script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config','<?= Layout::escape($ga4Id) ?>');</script>
<?php endif; ?>
</head>
<body>
<div class="glows" aria-hidden="true"></div>

<header class="site-header">
    <div class="container site-header__inner">
        <a href="/" class="brand" aria-label="Accueil cafetiereagrain.fr">
            <svg class="brand__mark" viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" style="width:36px;height:36px;flex-shrink:0;">
                <circle cx="32" cy="32" r="26" fill="none" stroke="#1f5742" stroke-width="1.2" opacity="0.22"/>
                <circle class="pam-halo" cx="32" cy="32" r="17" fill="none" stroke="#1f5742" stroke-width="1"/>
                <path class="pam-leaf-a" d="M 32 14 C 22 22, 22 38, 32 46 C 42 38, 42 22, 32 14 Z" fill="#1f5742" style="transform-origin:32px 50px;"/>
                <circle class="pam-dot" cx="32" cy="32" r="3.4" fill="#c6e870" style="transform-origin:32px 32px;"/>
            </svg>
            <span class="brand__name">
                <span class="brand__title">cafetière à grains</span>
                <span class="brand__tld">.FR · COMPARATEUR 2026</span>
            </span>
        </a>
        <nav class="nav" aria-label="Navigation principale">
            <a href="/blog">Blog</a>
            <a href="/blog?cluster=comparatif">Comparatifs</a>
            <a href="/blog?cluster=test">Tests</a>
            <a href="/blog?cluster=guide">Guides</a>
            <a href="/quiz">Quiz</a>
        </nav>
        <a href="/blog?cluster=comparatif" class="btn">Top 2026 →</a>
    </div>
</header>
