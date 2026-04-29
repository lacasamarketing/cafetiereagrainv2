<?php
// Quiz cafetière à grain : 5 questions → recommandation produit en BDD
declare(strict_types=1);
require __DIR__ . '/app/bootstrap.php';

use App\Core\Database;
use App\Core\Layout;

$cfg = Layout::loadConfig();
$pdo = Database::pdo();

// Récupère 12 produits actifs pour scorer ensuite côté front
$products = [];
try {
    $stmt = $pdo->query(
        "SELECT id, asin, slug, name, brand, type_cafetiere, price_eur, rating, ratings_total,
                main_image_url, verdict, summary_avis
         FROM products WHERE status = 'published' AND price_eur IS NOT NULL
         ORDER BY score_pertinence DESC LIMIT 12"
    );
    $products = $stmt->fetchAll();
} catch (Throwable $e) {
    error_log('quiz.php DB: ' . $e->getMessage());
}

$pageTitle = 'Quiz : trouve ta cafetière à grain idéale en 60 secondes';
$pageDescription = 'En 5 questions on te recommande la cafetière à grain qui colle à ton usage : budget, type de café, espace, bruit, expertise.';
$canonical = ($cfg['base_url'] ?? 'https://cafetiereagrain.fr') . '/quiz';

require __DIR__ . '/partials/header.php';
?>

<main class="quiz-page">
    <div class="container">

        <header class="quiz-header">
            <div class="quiz-eyebrow">Quiz personnalisé</div>
            <h1 class="display quiz-title">Quelle cafetière à grain pour <span class="quiz-title__accent">toi</span> ?</h1>
            <p class="quiz-lead">5 questions ultra rapides. On croise tes critères avec nos 21 machines analysées et on te recommande la plus cohérente.</p>
        </header>

        <div class="quiz-card" id="quiz-app">
            <div class="quiz-progress">
                <div class="quiz-progress__bar" id="quiz-bar"></div>
                <div class="quiz-progress__text" id="quiz-text">Question 1 / 5</div>
            </div>
            <div class="quiz-step-container" id="quiz-steps"></div>
        </div>

    </div>
</main>

<script>
(function(){
    var QUESTIONS = [
        {
            id: 'usage',
            label: 'Combien de cafés par jour à la maison ?',
            options: [
                { value: 'low',   label: '1 à 2 cafés (occasionnel)' },
                { value: 'mid',   label: '3 à 5 cafés (quotidien régulier)' },
                { value: 'high',  label: '6+ cafés (intensif, plusieurs personnes)' },
            ]
        },
        {
            id: 'drink',
            label: 'Quel type de café tu préfères ?',
            options: [
                { value: 'espresso', label: 'Espresso pur, court et corsé' },
                { value: 'lungo',    label: 'Café lungo / café long' },
                { value: 'milk',     label: 'Cappuccino, latte macchiato avec mousse de lait' },
                { value: 'all',      label: 'Un peu de tout, je veux le choix' },
            ]
        },
        {
            id: 'budget',
            label: 'Quel est ton budget max ?',
            options: [
                { value: 400,  label: 'Moins de 400 €' },
                { value: 700,  label: '400 à 700 €' },
                { value: 1100, label: '700 à 1100 €' },
                { value: 9999, label: 'Plus de 1100 €, je veux le top' },
            ]
        },
        {
            id: 'space',
            label: 'Espace cuisine disponible ?',
            options: [
                { value: 'small',  label: 'Petit comptoir, je veux compact' },
                { value: 'medium', label: 'Espace standard, taille classique OK' },
                { value: 'big',    label: 'Grande cuisine, peu importe la taille' },
            ]
        },
        {
            id: 'noise',
            label: 'Le bruit du broyeur, c\'est important pour toi ?',
            options: [
                { value: 'silent', label: 'Critique : machine très silencieuse' },
                { value: 'normal', label: 'Normal, du moment que ça reste raisonnable' },
                { value: 'idgaf',  label: 'Pas un sujet, je veux la performance' },
            ]
        },
    ];

    var PRODUCTS = <?= json_encode($products, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    var current = 0;
    var answers = {};
    var stepsEl = document.getElementById('quiz-steps');
    var barEl   = document.getElementById('quiz-bar');
    var textEl  = document.getElementById('quiz-text');

    function renderStep() {
        if (current >= QUESTIONS.length) return showResult();
        var q = QUESTIONS[current];
        var html = '<div class="quiz-step">' +
            '<h2 class="quiz-step__label">' + q.label + '</h2>' +
            '<div class="quiz-step__options">';
        q.options.forEach(function(opt, i){
            html += '<button class="quiz-option" data-value="' + opt.value + '" type="button">' +
                '<span class="quiz-option__num">' + String.fromCharCode(65+i) + '</span>' +
                '<span class="quiz-option__label">' + opt.label + '</span>' +
                '</button>';
        });
        html += '</div></div>';
        stepsEl.innerHTML = html;
        barEl.style.width = ((current / QUESTIONS.length) * 100) + '%';
        textEl.textContent = 'Question ' + (current + 1) + ' / ' + QUESTIONS.length;
        stepsEl.querySelectorAll('.quiz-option').forEach(function(btn){
            btn.addEventListener('click', function(){
                var v = btn.dataset.value;
                if (!isNaN(parseFloat(v))) v = parseFloat(v);
                answers[QUESTIONS[current].id] = v;
                current++;
                renderStep();
            });
        });
    }

    function scoreProducts() {
        return PRODUCTS.map(function(p){
            var score = 0;
            var price = parseFloat(p.price_eur) || 0;
            var name = (p.name || '').toLowerCase();
            var verdict = (p.verdict || '').toLowerCase();
            var pool = name + ' ' + verdict;

            // Budget
            if (typeof answers.budget === 'number') {
                if (price <= answers.budget) score += 30;
                else if (price <= answers.budget * 1.15) score += 10;
                else score -= 20;
            }
            // Drink type : LatteGo / mousseur / vapeur pour cappuccino
            if (answers.drink === 'milk') {
                if (/lattego|mousseur|cappuccino|vapeur|carafe.*lait|one.touch|barista/.test(pool)) score += 25;
            } else if (answers.drink === 'espresso') {
                if (/espresso|broyeur|grain/.test(pool)) score += 10;
            } else if (answers.drink === 'all') {
                if (/12 specialites|programme|touch|barista|tactile|spécialités|profils/.test(pool)) score += 15;
            }
            // Usage volume
            if (answers.usage === 'high' && /pro|professionnel|grande capacite|2 cafes|reservoir/.test(pool)) score += 10;
            if (answers.usage === 'low' && /compact|simple|debutant|essentiel/.test(pool)) score += 10;
            // Space
            if (answers.space === 'small' && /compact|slim|mini|petit/.test(pool)) score += 15;
            if (answers.space === 'big' && /haute gamme|premium|grande/.test(pool)) score += 5;
            // Noise
            if (answers.noise === 'silent' && /silencieuse|silencieux|ceramique|broyeur ceramique/.test(pool)) score += 15;
            // Rating boost
            score += (parseFloat(p.rating) || 0) * 4;
            // Reviews boost
            if ((parseInt(p.ratings_total,10)||0) > 1000) score += 5;
            return Object.assign({}, p, { score: score });
        }).sort(function(a, b){ return b.score - a.score; });
    }

    function showResult() {
        var ranked = scoreProducts();
        var top = ranked.slice(0, 3);
        barEl.style.width = '100%';
        textEl.textContent = 'Résultat';
        var html = '<div class="quiz-result">' +
            '<div class="quiz-result__head">' +
              '<div class="quiz-result__badge">Notre recommandation</div>' +
              '<h2 class="display quiz-result__title">Voici la cafetière qui te correspond</h2>' +
              '<p class="quiz-result__lead">Basé sur tes réponses · budget, type de café, espace et bruit croisés avec les retours utilisateurs.</p>' +
            '</div>' +
            '<div class="quiz-result__list">';
        top.forEach(function(p, i){
            var img = p.main_image_url || '';
            var name = (p.brand ? p.brand + ' ' : '') + p.name;
            var price = p.price_eur ? Math.round(p.price_eur) + ' €' : '';
            var rating = p.rating ? parseFloat(p.rating).toFixed(1) + '/5' : '';
            html += '<a class="quiz-product" href="/cafetiere/' + encodeURIComponent(p.asin || p.slug) + '">' +
                (i === 0 ? '<div class="quiz-product__rank">★ Top match</div>' : '<div class="quiz-product__rank quiz-product__rank--alt">Alternative</div>') +
                '<div class="quiz-product__img">' + (img ? '<img src="' + img + '" alt="' + name + '" loading="lazy">' : '<span class="quiz-product__noimg">📷</span>') + '</div>' +
                '<div class="quiz-product__body">' +
                  (p.brand ? '<div class="quiz-product__brand">' + p.brand + '</div>' : '') +
                  '<h3 class="quiz-product__name">' + (p.name || '') + '</h3>' +
                  (rating ? '<div class="quiz-product__rating">★ ' + rating + (p.ratings_total ? ' (' + p.ratings_total + ' avis)' : '') + '</div>' : '') +
                  '<div class="quiz-product__price">' + price + '</div>' +
                  (p.verdict ? '<p class="quiz-product__verdict">' + (p.verdict.length > 140 ? p.verdict.slice(0,140) + '…' : p.verdict) + '</p>' : '') +
                  '<span class="quiz-product__cta">Voir la fiche complète →</span>' +
                '</div>' +
                '</a>';
        });
        html += '</div>' +
            '<div class="quiz-result__actions">' +
              '<button type="button" class="btn btn--ghost" onclick="location.reload()">↻ Refaire le quiz</button>' +
              '<a href="/blog?cluster=comparatif" class="btn btn--primary">Voir tous les comparatifs →</a>' +
            '</div>' +
            '</div>';
        stepsEl.innerHTML = html;
    }

    renderStep();
})();
</script>

<style>
.quiz-page { padding: 5rem 0 6rem; }
.quiz-header { text-align: center; max-width: 720px; margin: 0 auto 3rem; }
.quiz-eyebrow {
    display: inline-block;
    font-family: 'JetBrains Mono', monospace;
    font-size: .72rem; font-weight: 600;
    color: var(--ink-2, #4a2c14);
    letter-spacing: .15em; text-transform: uppercase;
    background: var(--cream-2, #f3ece0);
    padding: .4em 1em; border-radius: 999px;
    margin-bottom: 1.5rem;
}
.quiz-title { font-size: clamp(2rem, 4.5vw, 3.5rem); line-height: 1.05; margin: 0 0 1rem; }
.quiz-title__accent { color: var(--ink-2, #4a2c14); font-style: italic; font-weight: 700; position: relative; }
.quiz-title__accent::after { content: ''; position: absolute; left: 0; right: 0; bottom: .05em; height: .3em; background: rgba(212,165,116,0.4); z-index: -1; border-radius: 2px; }
.quiz-lead { color: var(--muted, #6f5640); font-size: 1.1rem; line-height: 1.6; }

.quiz-card {
    max-width: 720px;
    margin: 0 auto;
    background: #fff;
    border: 1px solid var(--line, rgba(74,44,20,0.12));
    border-radius: 24px;
    padding: 2.5rem;
    box-shadow: 0 24px 48px -16px rgba(74,44,20,0.12);
}
.quiz-progress { margin-bottom: 2.5rem; }
.quiz-progress__bar { height: 5px; background: linear-gradient(90deg, #c89968, #a87149); border-radius: 999px; transition: width .4s cubic-bezier(.2,.8,.2,1); width: 0%; max-width: 100%; }
.quiz-progress__text { font-family: 'JetBrains Mono', monospace; font-size: .7rem; color: var(--muted, #6f5640); margin-top: .8rem; letter-spacing: .1em; text-transform: uppercase; }

.quiz-step__label { font-family: 'Fraunces', serif; font-size: clamp(1.4rem, 2.5vw, 1.85rem); font-weight: 600; color: var(--ink, #2a1810); margin: 0 0 2rem; line-height: 1.2; letter-spacing: -0.01em; }
.quiz-step__options { display: flex; flex-direction: column; gap: .8rem; }
.quiz-option {
    display: flex; align-items: center; gap: 1rem;
    padding: 1.1rem 1.3rem;
    background: var(--cream, #faf6ef);
    border: 2px solid var(--line, rgba(74,44,20,0.12));
    border-radius: 14px;
    cursor: pointer;
    text-align: left;
    transition: border-color .2s, transform .2s, background .2s;
    width: 100%;
    font-family: inherit;
}
.quiz-option:hover { border-color: var(--ink-2, #4a2c14); background: #fff; transform: translateX(4px); }
.quiz-option__num {
    flex: 0 0 36px; width: 36px; height: 36px;
    background: #fff; border: 1px solid var(--line, rgba(74,44,20,0.12));
    border-radius: 50%;
    display: inline-flex; align-items: center; justify-content: center;
    font-weight: 700; font-size: .9rem;
    color: var(--ink-2, #4a2c14);
}
.quiz-option:hover .quiz-option__num { background: var(--ink, #2a1810); color: #faf6ef; border-color: var(--ink, #2a1810); }
.quiz-option__label { flex: 1; font-size: 1rem; line-height: 1.4; color: var(--ink, #2a1810); font-weight: 500; }

/* Result */
.quiz-result__head { text-align: center; margin-bottom: 2.5rem; }
.quiz-result__badge {
    display: inline-block;
    font-family: 'JetBrains Mono', monospace;
    font-size: .7rem; font-weight: 700;
    background: linear-gradient(135deg, #c89968, #a87149);
    color: #fff;
    padding: .4em 1em; border-radius: 999px;
    letter-spacing: .12em; text-transform: uppercase;
    margin-bottom: 1rem;
}
.quiz-result__title { font-size: clamp(1.6rem, 3vw, 2.4rem); margin: 0 0 .8rem; }
.quiz-result__lead { color: var(--muted, #6f5640); font-size: 1rem; line-height: 1.5; }
.quiz-result__list { display: grid; gap: 1.2rem; }
.quiz-product {
    display: grid;
    grid-template-columns: 100px 1fr;
    gap: 1.2rem;
    padding: 1.2rem;
    background: var(--cream, #faf6ef);
    border: 2px solid var(--line, rgba(74,44,20,0.12));
    border-radius: 16px;
    text-decoration: none;
    color: inherit;
    transition: all .2s;
    position: relative;
}
.quiz-product:hover { transform: translateY(-3px); border-color: var(--ink-2, #4a2c14); box-shadow: 0 12px 24px rgba(74,44,20,.1); }
.quiz-product__rank {
    position: absolute; top: -12px; left: 1.2rem;
    background: linear-gradient(135deg, #c89968, #a87149);
    color: #fff; font-weight: 700; font-size: .7rem;
    padding: .35em .9em; border-radius: 999px;
    letter-spacing: .1em; text-transform: uppercase;
}
.quiz-product__rank--alt { background: var(--ink-2, #4a2c14); }
.quiz-product__img { width: 100px; height: 100px; background: #fff; border-radius: 10px; display: flex; align-items: center; justify-content: center; padding: .5rem; }
.quiz-product__img img { max-width: 100%; max-height: 100%; object-fit: contain; }
.quiz-product__noimg { font-size: 2rem; opacity: .3; }
.quiz-product__brand { font-size: .7rem; text-transform: uppercase; letter-spacing: .12em; color: var(--ink-2, #4a2c14); font-weight: 600; }
.quiz-product__name { font-family: 'Fraunces', serif; font-size: 1.05rem; font-weight: 600; margin: .25rem 0 .4rem; line-height: 1.2; color: var(--ink, #2a1810); }
.quiz-product__rating { font-size: .85rem; color: #c89968; font-weight: 600; margin-bottom: .25rem; }
.quiz-product__price { font-family: 'Fraunces', serif; font-size: 1.3rem; font-weight: 700; color: var(--ink, #2a1810); margin-bottom: .4rem; }
.quiz-product__verdict { font-size: .85rem; color: var(--muted, #6f5640); line-height: 1.5; margin: .3rem 0; font-style: italic; }
.quiz-product__cta { font-size: .85rem; color: var(--ink-2, #4a2c14); font-weight: 600; }
.quiz-result__actions { display: flex; gap: 1rem; justify-content: center; margin-top: 2rem; flex-wrap: wrap; }

@media (max-width: 600px) {
    .quiz-card { padding: 1.5rem; border-radius: 16px; }
    .quiz-product { grid-template-columns: 80px 1fr; gap: .8rem; padding: 1rem; }
    .quiz-product__img { width: 80px; height: 80px; }
}
</style>

<?php require __DIR__ . '/partials/footer.php'; ?>
