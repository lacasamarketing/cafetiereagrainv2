<?php
// Index temporaire cafetiereagrain.fr - page de transition durant migration
// Aucune dependance DB / classes Core - 100% autonome
declare(strict_types=1);

http_response_code(200);
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: public, max-age=300'); // 5 min cache CDN
?><!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5">
<meta name="theme-color" content="#faf6ef">
<title>Cafetière à grain — Le comparatif obsessionnel arrive bientôt</title>
<meta name="description" content="cafetiereagrain.fr fait peau neuve. Le comparatif indépendant des cafetières à grain revient très vite, plus complet, avec prix Amazon temps réel.">
<meta name="robots" content="noindex">
<link rel="canonical" href="https://cafetiereagrain.fr/">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@500;700;900&display=swap" rel="stylesheet">
<style>
:root {
  --bg: #faf6ef;
  --bg-2: #f3ece0;
  --espresso: #2a1810;
  --espresso-soft: #4a2c14;
  --caramel: #a8784e;
  --caramel-deep: #8b5a35;
  --crema: #d4a574;
  --gold: #c89863;
  --text-mute: #6f5640;
  --accent: #d97706;
}
* { box-sizing: border-box; margin: 0; padding: 0; }
html, body { background: var(--bg); color: var(--espresso); font-family: 'Inter', sans-serif; min-height: 100vh; overflow-x: hidden; }
body { display: flex; flex-direction: column; }
main {
  flex: 1; display: flex; align-items: center; justify-content: center;
  padding: 2rem; position: relative; min-height: 100vh;
}
.bg-decor {
  position: absolute; inset: 0; overflow: hidden; z-index: 0; pointer-events: none;
}
.bg-decor::before {
  content: ''; position: absolute; top: -10%; right: -10%; width: 60%; height: 60%;
  background: radial-gradient(circle, rgba(212, 165, 116, 0.18) 0%, transparent 60%);
  border-radius: 50%;
}
.bg-decor::after {
  content: ''; position: absolute; bottom: -20%; left: -10%; width: 50%; height: 50%;
  background: radial-gradient(circle, rgba(168, 120, 78, 0.12) 0%, transparent 60%);
  border-radius: 50%;
}
.bean {
  position: absolute; width: 18px; height: 26px;
  background: var(--caramel-deep); border-radius: 50% 50% 50% 50% / 60% 60% 40% 40%;
  opacity: 0.3; will-change: transform;
}
.bean::after {
  content: ''; position: absolute; inset: 8% 50% 8% 50%;
  border-right: 1.5px solid var(--bg);
  border-radius: 0 50% 50% 0 / 0 50% 50% 0;
}
.b1 { top: 12%; left: 8%; animation: float1 14s ease-in-out infinite; }
.b2 { top: 25%; right: 12%; animation: float2 18s ease-in-out infinite; opacity: .25; }
.b3 { bottom: 18%; left: 15%; animation: float3 16s ease-in-out infinite; opacity: .28; }
.b4 { bottom: 30%; right: 8%; animation: float4 20s ease-in-out infinite; opacity: .22; }
.b5 { top: 60%; left: 50%; animation: float5 22s ease-in-out infinite; opacity: .15; width: 14px; height: 20px; }
.b6 { top: 70%; right: 30%; animation: float6 17s ease-in-out infinite; opacity: .26; }
@keyframes float1 { 0%,100% { transform: translate(0,0) rotate(20deg); } 50% { transform: translate(40px,-50px) rotate(80deg); } }
@keyframes float2 { 0%,100% { transform: translate(0,0) rotate(-15deg); } 50% { transform: translate(-30px,40px) rotate(15deg); } }
@keyframes float3 { 0%,100% { transform: translate(0,0) rotate(45deg); } 50% { transform: translate(-40px,-60px) rotate(-30deg); } }
@keyframes float4 { 0%,100% { transform: translate(0,0) rotate(-30deg); } 50% { transform: translate(-50px,30px) rotate(70deg); } }
@keyframes float5 { 0%,100% { transform: translate(0,0) rotate(60deg); } 50% { transform: translate(40px,30px) rotate(-30deg); } }
@keyframes float6 { 0%,100% { transform: translate(0,0) rotate(75deg); } 50% { transform: translate(-30px,-40px) rotate(-25deg); } }
.container {
  position: relative; z-index: 1; max-width: 720px; text-align: center;
}
.eyebrow {
  display: inline-flex; align-items: center; gap: .6rem;
  padding: .5rem 1rem; background: #fff; border: 1px solid rgba(74,44,20,0.12);
  border-radius: 999px; font-size: .8rem; color: var(--caramel-deep);
  margin-bottom: 2rem; font-weight: 500; letter-spacing: .03em;
}
.eyebrow::before {
  content: ''; width: 6px; height: 6px; background: var(--accent);
  border-radius: 50%; animation: pulse 2s ease-in-out infinite;
}
@keyframes pulse { 0%,100% { opacity: 1; } 50% { opacity: .4; } }
h1 {
  font-family: 'Playfair Display', serif; font-size: clamp(2.2rem, 5vw, 3.6rem);
  line-height: 1.1; font-weight: 900; margin-bottom: 1.5rem;
  letter-spacing: -0.02em;
}
h1 .accent {
  color: var(--caramel-deep); font-style: italic; font-weight: 700;
  position: relative; display: inline-block;
}
h1 .accent::after {
  content: ''; position: absolute; left: 0; right: 0; bottom: 0.05em; height: 0.35em;
  background: rgba(212, 165, 116, 0.35); z-index: -1; border-radius: 2px;
}
.tagline {
  font-size: 1.15rem; color: var(--text-mute); line-height: 1.7;
  max-width: 540px; margin: 0 auto 2.5rem;
}
.cup {
  width: 120px; height: 120px; margin: 0 auto 2rem;
  position: relative;
  animation: cupFloat 3s ease-in-out infinite;
}
@keyframes cupFloat { 0%,100% { transform: translateY(0); } 50% { transform: translateY(-8px); } }
.cup svg { width: 100%; height: 100%; }
.steam {
  position: absolute; top: -30px; left: 50%; transform: translateX(-50%);
  width: 60px; height: 50px;
}
.steam-line {
  position: absolute; bottom: 0; width: 4px; height: 30px;
  background: linear-gradient(to top, rgba(168, 120, 78, 0.4), transparent);
  border-radius: 4px;
  animation: steam 3s ease-in-out infinite;
}
.steam-line:nth-child(1) { left: 20%; animation-delay: 0s; }
.steam-line:nth-child(2) { left: 50%; animation-delay: 0.5s; height: 36px; }
.steam-line:nth-child(3) { left: 78%; animation-delay: 1s; height: 28px; }
@keyframes steam {
  0% { opacity: 0; transform: translateY(0) scaleY(0.8); }
  50% { opacity: 0.6; transform: translateY(-14px) scaleY(1.1); }
  100% { opacity: 0; transform: translateY(-30px) scaleY(1.3); }
}
.progress {
  margin: 2rem auto; max-width: 320px;
  height: 8px; background: var(--bg-2); border-radius: 999px; overflow: hidden;
}
.progress-bar {
  height: 100%; background: linear-gradient(90deg, var(--caramel), var(--gold), var(--accent));
  border-radius: 999px;
  animation: progressMove 2.4s ease-in-out infinite;
  width: 35%;
}
@keyframes progressMove {
  0% { margin-left: -35%; }
  100% { margin-left: 100%; }
}
.steps {
  display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 1rem; margin: 3rem auto 2rem; max-width: 600px;
  text-align: left;
}
.step {
  padding: 1rem; background: #fff; border: 1px solid rgba(74,44,20,0.08);
  border-radius: 12px; display: flex; gap: .8rem; align-items: flex-start;
}
.step-icon {
  width: 32px; height: 32px; border-radius: 50%; flex-shrink: 0;
  background: var(--bg-2); color: var(--caramel-deep);
  display: flex; align-items: center; justify-content: center;
  font-weight: 700; font-size: .85rem;
}
.step strong { display: block; font-weight: 600; color: var(--espresso); margin-bottom: .15rem; font-size: .9rem; }
.step span { color: var(--text-mute); font-size: .8rem; line-height: 1.5; }
.step.done .step-icon { background: var(--caramel-deep); color: #fff; }
.step.done .step-icon::before { content: '✓'; }
.step.active .step-icon { background: var(--accent); color: #fff; animation: pulse 1.5s infinite; }
footer {
  padding: 2rem; text-align: center; color: var(--text-mute); font-size: .85rem;
  border-top: 1px solid rgba(74,44,20,0.08); background: #fff;
}
footer a { color: var(--caramel-deep); text-decoration: none; font-weight: 500; }
footer a:hover { text-decoration: underline; }
</style>
</head>
<body>
<main>
  <div class="bg-decor">
    <div class="bean b1"></div>
    <div class="bean b2"></div>
    <div class="bean b3"></div>
    <div class="bean b4"></div>
    <div class="bean b5"></div>
    <div class="bean b6"></div>
  </div>
  <div class="container">
    <div class="eyebrow">MIGRATION EN COURS &middot; SEO PRÉSERVÉ</div>

    <div class="cup">
      <div class="steam">
        <div class="steam-line"></div>
        <div class="steam-line"></div>
        <div class="steam-line"></div>
      </div>
      <svg viewBox="0 0 120 120" fill="none">
        <ellipse cx="60" cy="100" rx="40" ry="6" fill="#a8784e" opacity="0.15"/>
        <path d="M28 50 L92 50 L86 90 Q86 100 76 100 L44 100 Q34 100 34 90 Z" fill="#f5ede0" stroke="#8b5a35" stroke-width="2"/>
        <ellipse cx="60" cy="50" rx="32" ry="6" fill="#2a1810"/>
        <ellipse cx="60" cy="49" rx="26" ry="4" fill="#c89863" opacity="0.85"/>
        <path d="M92 60 Q108 60 108 75 Q108 90 92 88" stroke="#8b5a35" stroke-width="3" fill="none"/>
      </svg>
    </div>

    <h1>Le <span class="accent">nouveau cafetiereagrain.fr</span><br>arrive très bientôt.</h1>
    <p class="tagline">
      On refait tout au propre : prix Amazon temps réel via Rainforest, photos officielles, comparatifs honnêtes,
      tests sur 21 jours minimum. Le SEO et tes URLs préférées sont préservés.
    </p>

    <div class="progress">
      <div class="progress-bar"></div>
    </div>

    <div class="steps">
      <div class="step done">
        <div class="step-icon"></div>
        <div><strong>Audit &amp; sauvegarde</strong><span>52 articles, 23 produits Amazon enrichis.</span></div>
      </div>
      <div class="step active">
        <div class="step-icon">2</div>
        <div><strong>Migration en cours</strong><span>Stack PHP + MySQL, design café premium.</span></div>
      </div>
      <div class="step">
        <div class="step-icon">3</div>
        <div><strong>Mise en ligne</strong><span>Bientôt, avec prix Amazon temps réel.</span></div>
      </div>
    </div>

    <p style="margin-top: 2rem; color: var(--text-mute); font-size: .9rem;">
      Tes URLs d'articles préférés (<code style="background:var(--bg-2);padding:.15em .4em;border-radius:4px;font-size:.8em;">cafetiereagrain.fr/avis-...</code>) seront toutes préservées.
    </p>
  </div>
</main>

<footer>
  &copy; <?= date('Y') ?> Cafetiereagrain.fr &middot; Comparateur indépendant de cafetières à grain &middot;
  <a href="mailto:bonjour@lacasamarketing.fr">Contact</a> &middot;
  <a href="https://www.lacasamarketing.fr/" target="_blank" rel="noopener">La Casa Marketing</a>
</footer>
</body>
</html>
