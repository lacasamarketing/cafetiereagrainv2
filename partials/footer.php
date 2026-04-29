<?php declare(strict_types=1); ?>

<footer class="site-footer">
    <div class="container">

        <!-- BANNER : invitation à parcourir -->
        <div class="site-footer__banner">
            <div class="site-footer__banner-content">
                <h2 class="site-footer__banner-title">Trouve la cafetière qui te ressemble.</h2>
                <p class="site-footer__banner-lead">Comparatif indépendant · 21+ machines analysées · Aucun produit sponsorisé</p>
                <div class="site-footer__banner-cta">
                    <a href="/blog?cluster=comparatif" class="btn btn--primary btn--xl">Voir le top 2026 →</a>
                    <a href="/quiz" class="btn btn--ghost-light">Quiz : ma cafetière idéale</a>
                </div>
            </div>
            <div class="site-footer__banner-bean" aria-hidden="true">
                <svg viewBox="0 0 100 130" xmlns="http://www.w3.org/2000/svg">
                    <ellipse cx="50" cy="65" rx="40" ry="55" fill="#3d2418" stroke="#1a0d07" stroke-width="2"/>
                    <path d="M 50 12 Q 38 38 50 65 Q 62 92 50 118" fill="none" stroke="#c89968" stroke-width="3" stroke-linecap="round"/>
                </svg>
            </div>
        </div>

        <!-- COLONNES NAVIGATION -->
        <div class="site-footer__cols">
            <div class="site-footer__brand-col">
                <img src="/assets/img/logo-white.svg?v=<?= date('Ymd') ?>" alt="cafetière à grain" class="site-footer__logo">
                <p class="site-footer__tagline">Le comparateur indépendant des cafetières à grain. Avis honnêtes, retours utilisateurs croisés avec les fiches constructeurs.</p>
                <div class="site-footer__socials" aria-label="Reseaux sociaux">
                    <a href="https://www.youtube.com/@cafetiereagrain" target="_blank" rel="noopener" aria-label="Chaîne YouTube cafetiereagrain">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22.54 6.42a2.78 2.78 0 00-1.94-2C18.88 4 12 4 12 4s-6.88 0-8.6.46a2.78 2.78 0 00-1.94 2A29 29 0 001 11.75a29 29 0 00.46 5.33A2.78 2.78 0 003.4 19c1.72.46 8.6.46 8.6.46s6.88 0 8.6-.46a2.78 2.78 0 001.94-2 29 29 0 00.46-5.25 29 29 0 00-.46-5.33z"/><polygon points="9.75 15.02 15.5 11.75 9.75 8.48 9.75 15.02"/></svg>
                        <span class="site-footer__social-label">YouTube</span>
                    </a>
                </div>
            </div>

            <div class="site-footer__col">
                <h4 class="site-footer__h">Comparatifs</h4>
                <ul>
                    <li><a href="/blog?cluster=comparatif">Tous les comparatifs</a></li>
                    <li><a href="/magnifica-start-vs-evo">Magnifica Start vs Evo</a></li>
                    <li><a href="/delonghi-magnifica-start-vs-smart">Magnifica Start vs S Smart</a></li>
                    <li><a href="/cafetiere-grain-300-euros">Cafetière à 300 €</a></li>
                </ul>
            </div>

            <div class="site-footer__col">
                <h4 class="site-footer__h">Tests produits</h4>
                <ul>
                    <li><a href="/blog?cluster=test">Tous les tests</a></li>
                    <li><a href="/avis-delonghi-magnifica-evo">DeLonghi Magnifica Evo</a></li>
                    <li><a href="/avis-jura-ono-test-complet">Jura ONO</a></li>
                    <li><a href="/avis-philips-4300-lattego-ep4346">Philips 4300 LatteGo</a></li>
                </ul>
            </div>

            <div class="site-footer__col">
                <h4 class="site-footer__h">Guides &amp; conseils</h4>
                <ul>
                    <li><a href="/blog?cluster=guide">Tous les guides</a></li>
                    <li><a href="/entretien-machine-grains">Entretien machine</a></li>
                    <li><a href="/cout-cafe-annuel-le-comparateur-ultime">Coût café annuel</a></li>
                    <li><a href="/grain-vs-dosette-meilleur-cafe">Grain vs dosette</a></li>
                </ul>
            </div>

            <div class="site-footer__col">
                <h4 class="site-footer__h">Café en grain</h4>
                <ul>
                    <li><a href="/blog?cluster=cafes-en-grain">Tous les articles</a></li>
                    <li><a href="/differences-varietes-cafe-grain">Arabica vs Robusta</a></li>
                    <li><a href="/cafe-ethiopien-origine-saveurs">Café éthiopien</a></li>
                    <li><a href="/cafe-colombie-fruite-chocolate">Café colombien</a></li>
                </ul>
            </div>
        </div>

        <!-- BOTTOM BAR -->
        <div class="site-footer__bottom">
            <p class="site-footer__copy">&copy; <?= date('Y') ?> cafetiereagrain.fr · Comparateur indépendant</p>
            <div class="site-footer__legal-links">
                <a href="/mentions-legales">Mentions légales</a>
                <a href="/sitemap.xml">Plan du site</a>
                <a href="/quiz">Quiz cafetière</a>
            </div>
            <p class="site-footer__disclosure">En tant que Partenaire Amazon, ce site perçoit une commission sur les ventes générées via certains liens, sans surcoût pour toi.</p>
        </div>
    </div>
</footer>
</body>
</html>
