<?php
// Mentions légales cafetiereagrain.fr

declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

use App\Core\Layout;

$cfg = Layout::loadConfig();
$pageTitle = 'Mentions légales · Cafetière à grain';
$pageDescription = 'Mentions légales, hébergeur, éditeur et politique d\'affiliation de cafetiereagrain.fr.';
$canonical = ($cfg['base_url'] ?? '') . '/mentions-legales';

require __DIR__ . '/partials/header.php';
?>

<main class="article-wrap">
    <div class="container" style="max-width: 760px;">
        <a href="/" class="article-back">← Accueil</a>
        <h1 class="article-h1">Mentions légales.</h1>
        <p class="article-lede">Conditions de publication et transparence sur l'affiliation.</p>

        <div class="prose">
            <h2>Éditeur du site</h2>
            <p>
                Le site <strong>cafetiereagrain.fr</strong> est édité par<br>
                <strong>Antony Maurice</strong> — La Casa Marketing<br>
                Micro-entrepreneur immatriculé au RCS<br>
                Email : <a href="mailto:bonjour@lacasamarketing.fr">bonjour@lacasamarketing.fr</a>
            </p>

            <h2>Hébergement</h2>
            <p>
                Le site est hébergé par <strong>OVH SAS</strong><br>
                2 rue Kellermann — 59100 Roubaix — France<br>
                Téléphone : 09 72 10 10 07 — <a href="https://www.ovh.com" target="_blank" rel="noopener">www.ovh.com</a>
            </p>

            <h2>Propriété intellectuelle</h2>
            <p>
                L'ensemble des contenus présents sur le site (textes, illustrations, logos, scripts) sont
                la propriété de l'éditeur, sauf mention contraire. Toute reproduction, même partielle, est
                soumise à autorisation écrite préalable.
            </p>

            <h2>Affiliation et liens commerciaux</h2>
            <p>
                Ce site participe au <strong>Programme Partenaires d'Amazon EU</strong>, un programme d'affiliation
                conçu pour permettre à des sites de percevoir une rémunération grâce à la création de liens vers Amazon.fr.
            </p>
            <p>
                Lorsqu'un lien vers un produit pointe vers Amazon, il intègre notre identifiant de partenariat
                (<code>tag=lacasamarke08-21</code>). Si tu effectues un achat via ce lien, nous touchons une commission
                <strong>sans aucun surcoût pour toi</strong>. Cette commission n'influence jamais notre verdict éditorial :
                la sélection des modèles présentés et leur classement reposent uniquement sur l'analyse des avis vérifiés
                et des spécifications constructeurs, indépendamment de toute commission.
            </p>

            <h2>Données personnelles</h2>
            <p>
                Nous ne collectons aucune donnée personnelle directement identifiable sur ce site, à l'exception
                de l'adresse email lorsqu'elle est volontairement renseignée dans le formulaire de newsletter.
            </p>
            <p>
                Les statistiques de fréquentation reposent sur des données anonymisées (IP hashée, agent utilisateur).
                Aucune donnée n'est cédée à des tiers à des fins commerciales.
            </p>
            <p>
                Conformément au RGPD, tu peux demander l'effacement de tes données en écrivant à
                <a href="mailto:bonjour@lacasamarketing.fr">bonjour@lacasamarketing.fr</a>.
            </p>

            <h2>Cookies</h2>
            <p>
                Le site utilise uniquement des cookies fonctionnels (session, panel admin) et,
                si configurés, des outils d'analyse anonymisée (Plausible ou Google Analytics 4).
                Aucun cookie publicitaire ou de retargeting n'est déposé.
            </p>
        </div>
    </div>
</main>

<?php require __DIR__ . '/partials/footer.php';
