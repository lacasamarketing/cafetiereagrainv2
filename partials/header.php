<?php
declare(strict_types=1);
use App\Core\Layout;

$cfg = $cfg ?? Layout::loadConfig();
$baseUrl = $cfg['base_url'] ?? 'https://cafetiereagrain.fr';
$pageTitle = $pageTitle ?? 'Cafetière à grains — Le comparatif obsessionnel 2026';
$pageDescription = $pageDescription ?? 'Le comparatif indépendant des cafetières à grain.';
$canonical = $canonical ?? $baseUrl . ($_SERVER['REQUEST_URI'] ?? '/');
?><!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5">
<meta name="theme-color" content="#faf6ef">
<title><?= Layout::escape($pageTitle) ?></title>
<meta name="description" content="<?= Layout::escape($pageDescription) ?>">
<link rel="canonical" href="<?= Layout::escape($canonical) ?>">
<meta property="og:type" content="website">
<meta property="og:title" content="<?= Layout::escape($pageTitle) ?>">
<meta property="og:description" content="<?= Layout::escape($pageDescription) ?>">
<meta property="og:image" content="<?= Layout::escape($baseUrl . '/assets/img/og-default.png') ?>">
<link rel="icon" type="image/svg+xml" href="/assets/img/favicon.svg">
<link rel="alternate icon" href="/favicon.ico">
<link rel="stylesheet" href="/assets/css/custom.css?v=<?= date('Ymd') ?>">
<link rel="stylesheet" href="/assets/css/cafetiere-extras.css?v=<?= date('Ymd') ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;700;900&display=swap" rel="stylesheet">
</head>
<body>

<header class="site-header">
    <div class="container site-header__inner">
        <a href="/" class="brand" aria-label="Accueil cafetiereagrain.fr">
            <img src="/assets/img/logo.svg?v=<?= date('Ymd') ?>" alt="cafetière à grain" class="brand__logo">
        </a>
        <nav class="nav nav--desktop" aria-label="Navigation principale">
            <a href="/blog">Blog</a>
            <a href="/blog?cluster=comparatif">Comparatifs</a>
            <a href="/blog?cluster=test">Tests</a>
            <a href="/blog?cluster=guide">Guides</a>
            <a href="/quiz">Quiz</a>
        </nav>
        <a href="/blog?cluster=comparatif" class="btn btn--top2026">Top 2026 →</a>
        <button class="hamburger" type="button" aria-label="Ouvrir le menu" onclick="document.body.classList.add('nav-open')">
            <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true">
                <line x1="4" y1="7" x2="20" y2="7"/>
                <line x1="4" y1="12" x2="20" y2="12"/>
                <line x1="4" y1="17" x2="20" y2="17"/>
            </svg>
        </button>
    </div>
</header>

<!-- DRAWER MOBILE : SORTI du <header> pour echapper au stacking context -->
<div class="nav-overlay" onclick="document.body.classList.remove('nav-open')" aria-hidden="true"></div>
<nav class="nav nav--mobile" id="main-nav" aria-label="Navigation mobile">
    <button class="nav__close" type="button" aria-label="Fermer le menu" onclick="document.body.classList.remove('nav-open')">
        <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><line x1="6" y1="6" x2="18" y2="18"/><line x1="6" y1="18" x2="18" y2="6"/></svg>
    </button>
    <a href="/blog" onclick="document.body.classList.remove('nav-open')">Blog</a>
    <a href="/blog?cluster=comparatif" onclick="document.body.classList.remove('nav-open')">Comparatifs</a>
    <a href="/blog?cluster=test" onclick="document.body.classList.remove('nav-open')">Tests</a>
    <a href="/blog?cluster=guide" onclick="document.body.classList.remove('nav-open')">Guides</a>
    <a href="/quiz" onclick="document.body.classList.remove('nav-open')">Quiz</a>
</nav>
