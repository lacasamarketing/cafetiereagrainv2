<?php
// Page quiz cafetiereagrain.fr
// URL: /quiz/{usage} -> route via .htaccess vers quiz.php?usage={usage}
// Usages disponibles : terrasse, jardin, chambre, tigre

declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

use App\Core\Database;
use App\Core\Layout;

$cfg = Layout::loadConfig();

// Fix OVH FastCGI : recup usage depuis 3 sources possibles (GET, PATH_INFO, REQUEST_URI)
$usage = isset($_GET['usage']) ? (string)$_GET['usage'] : '';
if ($usage === '' && !empty($_SERVER['PATH_INFO'])) {
    $usage = trim((string)$_SERVER['PATH_INFO'], '/');
}
if ($usage === '' && !empty($_SERVER['REQUEST_URI'])) {
    $_path = parse_url((string)$_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '';
    if (preg_match('#^/quiz/([a-z0-9\-]+)/?$#i', $_path, $_m)) {
        $usage = $_m[1];
    }
}
$usage = preg_replace('/[^a-z0-9\-]/', '', strtolower($usage));

// Configs des 4 quiz
$quizConfigs = [
    'terrasse' => [
        'title' => 'Quel cafetière à grains pour ta terrasse ?',
        'subtitle' => 'En 4 questions, on identifie le modèle qui colle vraiment à ton extérieur.',
        'eyebrow' => '/01 — Quiz terrasse',
        'h1' => 'Trouve ton cafetière à grains pour terrasse',
        'meta_description' => 'Quiz personnalisé pour choisir le bon cafetière à grains selon la surface, le bruit et le budget de ta terrasse. Recommandation basée sur les retours utilisateurs.',
        'questions' => [
            [
                'id' => 'surface',
                'label' => 'Quelle surface fait ta terrasse ?',
                'options' => [
                    ['value' => 30,  'label' => 'Moins de 30 m²'],
                    ['value' => 80,  'label' => '30 à 80 m²'],
                    ['value' => 150, 'label' => '80 à 150 m²'],
                    ['value' => 300, 'label' => 'Plus de 150 m²'],
                ],
            ],
            [
                'id' => 'noise',
                'label' => 'Le bruit est-il un critère ?',
                'options' => [
                    ['value' => 25, 'label' => 'Silence quasi-total (repas, soirée)'],
                    ['value' => 40, 'label' => 'Plutôt discret'],
                    ['value' => 99, 'label' => 'Peu importe'],
                ],
            ],
            [
                'id' => 'power',
                'label' => 'As-tu une prise électrique accessible ?',
                'options' => [
                    ['value' => 'plug',    'label' => 'Oui, prise extérieure dispo'],
                    ['value' => 'battery', 'label' => 'Non, je préfère batterie ou solaire'],
                ],
            ],
            [
                'id' => 'budget',
                'label' => 'Quel budget max ?',
                'options' => [
                    ['value' => 50,  'label' => 'Moins de 50 €'],
                    ['value' => 150, 'label' => '50 à 150 €'],
                    ['value' => 300, 'label' => '150 à 300 €'],
                    ['value' => 999, 'label' => 'Plus de 300 €'],
                ],
            ],
        ],
    ],
    'jardin' => [
        'title' => 'Quel cafetière à grains pour ton jardin ?',
        'subtitle' => 'Surface, présence de tigre, accès secteur — on croise tes contraintes avec les vrais retours users.',
        'eyebrow' => '/02 — Quiz jardin',
        'h1' => 'Trouve ton cafetière à grains pour le jardin',
        'meta_description' => 'Quiz pour identifier le cafetière à grains de jardin idéal selon la surface, la présence de café en grain et le budget.',
        'questions' => [
            [
                'id' => 'surface',
                'label' => 'Surface de ton jardin ?',
                'options' => [
                    ['value' => 200,  'label' => 'Petit (< 200 m²)'],
                    ['value' => 500,  'label' => 'Moyen (200 à 500 m²)'],
                    ['value' => 1500, 'label' => 'Grand (plus de 500 m²)'],
                ],
            ],
            [
                'id' => 'tigre',
                'label' => 'Présence de café en grain ?',
                'options' => [
                    ['value' => 'yes', 'label' => 'Oui, c\'est le problème principal'],
                    ['value' => 'maybe', 'label' => 'Pas sûr, plutôt classiques'],
                    ['value' => 'no',  'label' => 'Non, je n\'en vois pas'],
                ],
            ],
            [
                'id' => 'power',
                'label' => 'Accès à une prise extérieure ?',
                'options' => [
                    ['value' => 'plug',    'label' => 'Oui, secteur dispo'],
                    ['value' => 'battery', 'label' => 'Non, je veux solaire ou autonome'],
                ],
            ],
            [
                'id' => 'budget',
                'label' => 'Budget max ?',
                'options' => [
                    ['value' => 100,  'label' => 'Moins de 100 €'],
                    ['value' => 300,  'label' => '100 à 300 €'],
                    ['value' => 999,  'label' => 'Plus de 300 €'],
                ],
            ],
        ],
    ],
    'chambre' => [
        'title' => 'Quel cafetière à grains pour ta chambre ?',
        'subtitle' => 'Silence, surface, USB ou secteur — la sélection adaptée à ton sommeil.',
        'eyebrow' => '/03 — Quiz chambre',
        'h1' => 'Trouve ton cafetière à grains pour la chambre',
        'meta_description' => 'Quiz pour choisir un cafetière à grains silencieux, adapté à ta chambre, ton budget et ton mode d\'alimentation préféré (USB, secteur).',
        'questions' => [
            [
                'id' => 'profile',
                'label' => 'Pour qui ?',
                'options' => [
                    ['value' => 'adult', 'label' => 'Adulte (chambre principale)'],
                    ['value' => 'kid',   'label' => 'Enfant ou bébé (sécurité prioritaire)'],
                ],
            ],
            [
                'id' => 'surface',
                'label' => 'Surface de la chambre ?',
                'options' => [
                    ['value' => 15, 'label' => 'Moins de 15 m²'],
                    ['value' => 30, 'label' => '15 à 30 m²'],
                    ['value' => 50, 'label' => 'Plus de 30 m²'],
                ],
            ],
            [
                'id' => 'power',
                'label' => 'Alimentation préférée ?',
                'options' => [
                    ['value' => 'usb',  'label' => 'USB rechargeable (mobile)'],
                    ['value' => 'plug', 'label' => 'Prise secteur fixe'],
                ],
            ],
            [
                'id' => 'budget',
                'label' => 'Budget max ?',
                'options' => [
                    ['value' => 30, 'label' => 'Moins de 30 €'],
                    ['value' => 60, 'label' => '30 à 60 €'],
                    ['value' => 200, 'label' => 'Plus de 60 €'],
                ],
            ],
        ],
    ],
    'tigre' => [
        'title' => 'Quel piège pour le café en grain ?',
        'subtitle' => 'Aedes albopictus n\'est pas attiré par l\'UV. Voici comment trouver le bon piège.',
        'eyebrow' => '/04 — Quiz anti-tigre',
        'h1' => 'Trouve ton piège anti café en grain',
        'meta_description' => 'Quiz spécialisé café en grain (Aedes albopictus) : trouve le piège CO2, aspirant ou anti-ponte adapté à ta zone et ton extérieur.',
        'questions' => [
            [
                'id' => 'zone',
                'label' => 'Où habites-tu ?',
                'options' => [
                    ['value' => 'south', 'label' => 'Sud de la France ou pourtour méditerranéen'],
                    ['value' => 'dom',   'label' => 'Outre-mer (Réunion, Antilles, Guyane)'],
                    ['value' => 'other', 'label' => 'Reste de la France métropolitaine'],
                ],
            ],
            [
                'id' => 'space',
                'label' => 'Espace à protéger ?',
                'options' => [
                    ['value' => 30,   'label' => 'Balcon ou petite terrasse'],
                    ['value' => 200,  'label' => 'Jardin standard'],
                    ['value' => 1000, 'label' => 'Grande propriété'],
                ],
            ],
            [
                'id' => 'method',
                'label' => 'Tu préfères capturer ou piéger les œufs ?',
                'options' => [
                    ['value' => 'capture', 'label' => 'Capturer les adultes (action immédiate)'],
                    ['value' => 'eggs',    'label' => 'Piéger les œufs (action long terme)'],
                    ['value' => 'both',    'label' => 'Les deux idéalement'],
                ],
            ],
            [
                'id' => 'budget',
                'label' => 'Budget max ?',
                'options' => [
                    ['value' => 100,  'label' => 'Moins de 100 €'],
                    ['value' => 300,  'label' => '100 à 300 €'],
                    ['value' => 999,  'label' => 'Plus de 300 €'],
                ],
            ],
        ],
    ],
];

// Bloc CSS partagé (utilisé par l'index ET les pages quiz)
$quizSharedCss = <<<CSS
<style>
.quiz-main { padding: 60px 0 100px; min-height: 70vh; }
.quiz-eyebrow { font-size: 12px; letter-spacing: .15em; text-transform: uppercase; color: var(--forest, #1f5742); font-family: 'Inter', sans-serif; margin: 24px 0 12px; font-weight: 500; }
.quiz-h1 { font-family: 'Fraunces', serif; font-size: clamp(32px, 5vw, 56px); font-weight: 500; line-height: 1.1; color: var(--forest, #1f5742); margin: 0 0 16px; }
.quiz-subtitle { font-size: clamp(16px, 1.6vw, 19px); color: #5b6660; max-width: 640px; margin: 0 0 48px; line-height: 1.6; }
.quiz-progress { position: relative; height: 4px; background: rgba(74,44,20,.12); border-radius: 4px; max-width: 720px; margin: 0 0 8px; overflow: hidden; }
.quiz-progress-bar { height: 100%; background: var(--lime, #c6e870); transition: width .3s cubic-bezier(.4,0,.2,1); }
.quiz-progress-text { display: block; font-size: 12px; color: #888; margin: 6px 0 32px; font-family: 'Inter', sans-serif; letter-spacing: .05em; }
.quiz-shell { background: #fff; border: 1px solid rgba(74,44,20,.12); border-radius: 18px; padding: 40px; max-width: 720px; box-shadow: 0 8px 28px rgba(74,44,20,.06); }
.quiz-question-label { font-family: 'Fraunces', serif; font-size: clamp(22px, 2.4vw, 28px); font-weight: 500; color: var(--forest, #1f5742); margin: 0 0 28px; line-height: 1.3; }
.quiz-options { display: flex; flex-direction: column; gap: 12px; }
.quiz-option { display: flex; align-items: center; gap: 14px; padding: 18px 22px; border: 1.5px solid rgba(74,44,20,.15); border-radius: 14px; background: #fafaf6; cursor: pointer; transition: all .2s; font-size: 16px; color: #2a3530; font-family: 'Inter', sans-serif; text-align: left; width: 100%; }
.quiz-option:hover { border-color: var(--forest, #1f5742); background: #fff; transform: translateX(2px); }
.quiz-option-bullet { width: 22px; height: 22px; border-radius: 50%; border: 2px solid rgba(74,44,20,.25); flex-shrink: 0; position: relative; transition: all .2s; }
.quiz-option:hover .quiz-option-bullet { border-color: var(--forest, #1f5742); }
.quiz-option-bullet::after { content: ''; position: absolute; inset: 4px; border-radius: 50%; background: var(--lime, #c6e870); transform: scale(0); transition: transform .2s; }
.quiz-option.is-selected { border-color: var(--forest, #1f5742); background: #fff; }
.quiz-option.is-selected .quiz-option-bullet::after { transform: scale(1); }
.quiz-nav { display: flex; justify-content: space-between; margin-top: 32px; gap: 12px; }
.quiz-btn { font-family: 'Inter', sans-serif; font-size: 15px; font-weight: 500; padding: 14px 28px; border-radius: 999px; border: none; cursor: pointer; transition: all .2s; letter-spacing: .02em; text-decoration: none; display: inline-block; }
.quiz-btn--primary { background: var(--forest, #1f5742); color: #fff; }
.quiz-btn--primary:hover { background: #2a6a52; }
.quiz-btn--primary:disabled { opacity: .35; cursor: not-allowed; }
.quiz-btn--ghost { background: transparent; color: var(--forest, #1f5742); border: 1.5px solid rgba(74,44,20,.2); }
.quiz-btn--ghost:hover { background: rgba(74,44,20,.05); }
.quiz-result-shell { background: linear-gradient(135deg, #fff 0%, #f5f8f0 100%); border: 1.5px solid var(--lime, #c6e870); border-radius: 24px; padding: 48px; max-width: 820px; box-shadow: 0 12px 40px rgba(74,44,20,.1); }
.quiz-result-eyebrow { display: inline-block; background: var(--lime, #c6e870); color: var(--forest, #1f5742); padding: 6px 14px; border-radius: 999px; font-size: 11px; letter-spacing: .15em; text-transform: uppercase; font-weight: 600; margin-bottom: 18px; }
.quiz-result-title { font-family: 'Fraunces', serif; font-size: clamp(28px, 3.5vw, 40px); font-weight: 500; color: var(--forest, #1f5742); margin: 0 0 12px; line-height: 1.15; }
.quiz-result-reason { color: #5b6660; font-size: 16px; line-height: 1.6; margin: 0 0 32px; max-width: 600px; }
.quiz-result-card { display: grid; grid-template-columns: 180px 1fr; gap: 32px; background: #fff; border: 1px solid rgba(74,44,20,.1); border-radius: 16px; padding: 24px; align-items: center; }
.quiz-result-card img { width: 100%; height: 180px; object-fit: contain; background: #f5f8f0; border-radius: 12px; padding: 12px; }
.quiz-result-card-meta { display: flex; gap: 18px; flex-wrap: wrap; font-size: 13px; color: #5b6660; margin: 4px 0 12px; }
.quiz-result-card-meta strong { color: var(--forest, #1f5742); }
.quiz-result-card h3 { font-family: 'Fraunces', serif; font-size: 22px; color: var(--forest, #1f5742); margin: 0 0 6px; font-weight: 500; }
.quiz-result-card p { color: #5b6660; font-size: 14px; margin: 0 0 16px; line-height: 1.5; }
.quiz-result-actions { display: flex; gap: 12px; flex-wrap: wrap; margin-top: 8px; }
.quiz-result-rating { display: flex; align-items: center; gap: 6px; color: var(--forest, #1f5742); font-weight: 600; font-size: 14px; }
.quiz-result-runners { margin-top: 36px; }
.quiz-result-runners h4 { font-family: 'Inter', sans-serif; font-size: 12px; letter-spacing: .15em; text-transform: uppercase; color: #888; font-weight: 500; margin: 0 0 16px; }
.quiz-result-runners-list { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.quiz-runner { background: #fff; border: 1px solid rgba(74,44,20,.08); border-radius: 12px; padding: 14px 18px; display: flex; justify-content: space-between; align-items: center; gap: 12px; text-decoration: none; color: inherit; transition: all .2s; }
.quiz-runner:hover { border-color: var(--forest, #1f5742); transform: translateY(-1px); }
.quiz-runner-name { font-weight: 500; color: var(--forest, #1f5742); font-size: 14px; }
.quiz-runner-tech { font-size: 12px; color: #888; }
.quiz-runner-rate { font-size: 13px; color: var(--forest, #1f5742); font-weight: 600; white-space: nowrap; }
.quiz-index { padding: 40px 0 80px; }
.quiz-index-eyebrow { font-size: 12px; letter-spacing: .15em; text-transform: uppercase; color: var(--forest, #1f5742); font-family: 'Inter', sans-serif; margin: 0 0 12px; }
.quiz-cards { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-top: 48px; }
.quiz-card { display: block; background: #fff; border: 1.5px solid rgba(74,44,20,.12); border-radius: 20px; padding: 32px; text-decoration: none; color: inherit; transition: all .25s cubic-bezier(.4,0,.2,1); position: relative; overflow: hidden; }
.quiz-card:hover { border-color: var(--forest, #1f5742); transform: translateY(-3px); box-shadow: 0 16px 40px rgba(74,44,20,.1); }
.quiz-card-num { font-family: 'Inter', sans-serif; font-size: 12px; letter-spacing: .15em; color: #999; }
.quiz-card h2 { font-family: 'Fraunces', serif; font-size: 28px; color: var(--forest, #1f5742); margin: 8px 0 12px; font-weight: 500; }
.quiz-card p { color: #5b6660; font-size: 15px; line-height: 1.5; margin: 0 0 18px; }
.quiz-card-cta { color: var(--forest, #1f5742); font-weight: 500; font-size: 14px; }
@media (max-width: 720px) {
  .quiz-shell, .quiz-result-shell { padding: 28px 22px; }
  .quiz-result-card { grid-template-columns: 1fr; }
  .quiz-result-card img { height: 160px; }
  .quiz-result-runners-list { grid-template-columns: 1fr; }
  .quiz-cards { grid-template-columns: 1fr; }
}
</style>
CSS;

// Fallback : page index des 4 quiz si usage absent ou inconnu
if (!isset($quizConfigs[$usage])) {
    $pageTitle = 'Quiz : trouve ton cafetière à grains · Cafetière à grain';
    $pageDescription = 'Quatre quiz interactifs pour identifier le cafetière à grains adapté à ton usage : terrasse, jardin, chambre ou anti-tigre.';
    $canonical = ($cfg['base_url'] ?? '') . '/quiz';
    require __DIR__ . '/partials/header.php';
    echo $quizSharedCss;
    ?>
    <main class="article-wrap">
      <div class="container quiz-index">
        <div class="quiz-index-eyebrow">/ Quiz</div>
        <h1 class="article-h1">Quel cafetière à grains pour toi ?</h1>
        <p class="article-lead">Quatre quiz, 4 usages. En moins de 60 secondes, on croise tes contraintes avec les retours utilisateurs des produits qu'on suit, et on te recommande celui qui colle.</p>

        <div class="quiz-cards">
          <a href="/quiz/terrasse" class="quiz-card quiz-card--terrasse">
            <span class="quiz-card-num">01</span>
            <h2>Terrasse</h2>
            <p>Surface, bruit, prise élec, budget. Le bon modèle pour tes apéros sans moustiques.</p>
            <span class="quiz-card-cta">Commencer →</span>
          </a>
          <a href="/quiz/jardin" class="quiz-card quiz-card--jardin">
            <span class="quiz-card-num">02</span>
            <h2>Jardin</h2>
            <p>Petit, moyen ou grand jardin, présence de tigre ou non — la reco pour couvrir vraiment.</p>
            <span class="quiz-card-cta">Commencer →</span>
          </a>
          <a href="/quiz/chambre" class="quiz-card quiz-card--chambre">
            <span class="quiz-card-num">03</span>
            <h2>Chambre</h2>
            <p>Silence, sécurité enfant, USB ou secteur — pour dormir sans bzzz.</p>
            <span class="quiz-card-cta">Commencer →</span>
          </a>
          <a href="/quiz/tigre" class="quiz-card quiz-card--tigre">
            <span class="quiz-card-num">04</span>
            <h2>Anti café en grain</h2>
            <p>Aedes albopictus n'est pas attiré par l'UV. Le bon piège selon ta zone et la méthode.</p>
            <span class="quiz-card-cta">Commencer →</span>
          </a>
        </div>
      </div>
    </main>
    <?php
    require __DIR__ . '/partials/footer.php';
    exit;
}

$quiz = $quizConfigs[$usage];

// Charge tous les produits éligibles depuis la BDD :
// - status published
// - target_use = $usage  OU  match plausible (ex: tigre→CO2/aspirant, chambre→silencieux)
// On élargit volontairement pour avoir au moins 3-5 candidats à scorer.
try {
    $pdo = Database::pdo();

    if ($usage === 'tigre') {
        // Anti-tigre : exclure UV (inefficace), favoriser CO2/aspirant/larvaire
        $stmt = $pdo->prepare(
            "SELECT * FROM products
             WHERE status = 'published'
               AND (target_use = 'tigre' OR technology IN ('co2','propane','aspirant','larvaire'))
             ORDER BY rating DESC, reviews_count DESC"
        );
        $stmt->execute();
    } elseif ($usage === 'chambre') {
        // Chambre : exclure les pros bruyants, favoriser UV/aspirant silencieux
        $stmt = $pdo->prepare(
            "SELECT * FROM products
             WHERE status = 'published'
               AND (target_use = 'chambre' OR technology IN ('uv','aspirant'))
               AND (target_use IS NULL OR target_use <> 'pro')
             ORDER BY rating DESC, reviews_count DESC"
        );
        $stmt->execute();
    } else {
        // Terrasse/jardin : on prend tout sauf produits typés strictement chambre
        $stmt = $pdo->prepare(
            "SELECT * FROM products
             WHERE status = 'published'
               AND (target_use IS NULL OR target_use IN (?, 'terrasse', 'jardin', 'tigre', 'pro'))
             ORDER BY rating DESC, reviews_count DESC"
        );
        $stmt->execute([$usage]);
    }
    $products = $stmt->fetchAll();
} catch (Throwable $e) {
    $products = [];
}

// Si on a 0 produit éligible, on affiche un message au lieu d'un quiz vide
if (empty($products)) {
    $pageTitle = $quiz['title'] . ' · Cafetière à grain';
    require __DIR__ . '/partials/header.php';
    echo '<main class="article-wrap"><div class="container"><h1 class="article-h1">' . htmlspecialchars($quiz['h1']) . '</h1><p class="text-muted">Aucun produit éligible pour cette catégorie pour le moment. Reviens dans quelques jours, notre routine de découverte ajoute de nouveaux modèles chaque semaine.</p><a href="/quiz" class="btn-primary">← Retour aux quiz</a></div></main>';
    require __DIR__ . '/partials/footer.php';
    exit;
}

// Prépare les produits pour le JS : un sous-ensemble de champs (pas de cons/pitch internes)
$productsJs = array_map(function ($p) {
    return [
        'slug' => $p['slug'],
        'name' => $p['name'],
        'brand' => $p['brand'],
        'asin' => $p['asin'],
        'technology' => $p['technology'],
        'target_use' => $p['target_use'],
        'surface_m2' => $p['surface_m2'] !== null ? (int)$p['surface_m2'] : null,
        'noise_db' => $p['noise_db'] !== null ? (float)$p['noise_db'] : null,
        'price_eur' => $p['price_eur'] !== null ? (float)$p['price_eur'] : null,
        'rating' => $p['rating'] !== null ? (float)$p['rating'] : null,
        'reviews_count' => $p['reviews_count'] !== null ? (int)$p['reviews_count'] : null,
        'badge' => $p['badge'],
        'verdict' => $p['verdict'],
        'image_url' => $p['image_url'],
    ];
}, $products);

// SEO
$pageTitle = $quiz['title'] . ' · Cafetière à grain';
$pageDescription = mb_substr($quiz['meta_description'], 0, 158);
$canonical = ($cfg['base_url'] ?? '') . '/quiz/' . $usage;

require __DIR__ . '/partials/header.php';
echo $quizSharedCss;
?>

<main class="quiz-main">
  <div class="container">
    <a href="/quiz" class="article-back">← Tous les quiz</a>

    <div class="quiz-eyebrow"><?= htmlspecialchars($quiz['eyebrow']) ?></div>
    <h1 class="quiz-h1"><?= htmlspecialchars($quiz['h1']) ?></h1>
    <p class="quiz-subtitle"><?= htmlspecialchars($quiz['subtitle']) ?></p>

    <div class="quiz-progress">
      <div class="quiz-progress-bar" id="quizProgress" style="width: 0%"></div>
      <span class="quiz-progress-text" id="quizProgressText">Question 1 / <?= count($quiz['questions']) ?></span>
    </div>

    <div class="quiz-shell" id="quizShell">
      <!-- Les questions sont injectées par JS -->
    </div>

    <div class="quiz-result" id="quizResult" style="display: none;">
      <!-- Le résultat est injecté par JS à la fin -->
    </div>
  </div>
</main>

<script>
(function () {
  const QUIZ = <?= json_encode($quiz, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
  const PRODUCTS = <?= json_encode($productsJs, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
  const USAGE = <?= json_encode($usage) ?>;

  const shell = document.getElementById('quizShell');
  const result = document.getElementById('quizResult');
  const progress = document.getElementById('quizProgress');
  const progressText = document.getElementById('quizProgressText');

  let current = 0;
  const answers = {};

  function render() {
    if (current >= QUIZ.questions.length) {
      shell.style.display = 'none';
      progress.parentElement.style.display = 'none';
      progressText.style.display = 'none';
      renderResult();
      return;
    }
    const q = QUIZ.questions[current];
    const optionsHtml = q.options.map((o, i) => `
      <button type="button" class="quiz-option ${answers[q.id] === o.value ? 'is-selected' : ''}" data-value="${o.value}">
        <span class="quiz-option-bullet"></span>
        <span>${o.label}</span>
      </button>
    `).join('');

    shell.innerHTML = `
      <h2 class="quiz-question-label">${q.label}</h2>
      <div class="quiz-options" id="quizOpts">${optionsHtml}</div>
      <div class="quiz-nav">
        <button type="button" class="quiz-btn quiz-btn--ghost" id="btnBack" ${current === 0 ? 'style="visibility:hidden"' : ''}>← Précédent</button>
        <button type="button" class="quiz-btn quiz-btn--primary" id="btnNext" ${answers[q.id] === undefined ? 'disabled' : ''}>${current === QUIZ.questions.length - 1 ? 'Voir mon piège →' : 'Suivant →'}</button>
      </div>
    `;
    progress.style.width = ((current) / QUIZ.questions.length * 100) + '%';
    progressText.textContent = `Question ${current + 1} / ${QUIZ.questions.length}`;

    document.getElementById('quizOpts').addEventListener('click', (e) => {
      const btn = e.target.closest('.quiz-option');
      if (!btn) return;
      let val = btn.dataset.value;
      if (!isNaN(parseFloat(val))) val = parseFloat(val);
      answers[q.id] = val;
      document.querySelectorAll('.quiz-option').forEach(el => el.classList.remove('is-selected'));
      btn.classList.add('is-selected');
      document.getElementById('btnNext').disabled = false;
    });

    document.getElementById('btnNext').addEventListener('click', () => {
      if (answers[q.id] === undefined) return;
      current++;
      render();
    });
    if (current > 0) {
      document.getElementById('btnBack').addEventListener('click', () => { current--; render(); });
    }
  }

  function scoreProduct(p) {
    let score = 0;
    const A = answers;

    // Score de base : note réelle Amazon (max +20)
    if (p.rating) score += p.rating * 4;
    // Bonus avis nombreux (preuve sociale, max +5)
    if (p.reviews_count) score += Math.min(5, Math.log10(p.reviews_count + 1));

    // Surface match (max +25 si match parfait, malus si trop petit)
    if (A.surface !== undefined && p.surface_m2 !== null) {
      const need = parseFloat(A.surface);
      if (p.surface_m2 >= need) score += 20;
      else score -= 15;
    }
    if (A.space !== undefined && p.surface_m2 !== null) {
      const need = parseFloat(A.space);
      if (p.surface_m2 >= need) score += 20;
      else score -= 15;
    }

    // Bruit (chambre, terrasse silence)
    if (A.noise !== undefined && p.noise_db !== null) {
      const tol = parseFloat(A.noise);
      if (p.noise_db <= tol) score += 12;
      else score -= 8;
    }

    // Budget (gros bonus si dans le budget)
    if (A.budget !== undefined && p.price_eur !== null) {
      const max = parseFloat(A.budget);
      if (p.price_eur <= max) score += 18;
      else score -= 25;
    }

    // Alimentation
    if (A.power === 'battery' || A.power === 'usb') {
      // Si solaire ou aspirant batterie -> bonus
      if (p.technology === 'solaire' || p.technology === 'aspirant') score += 10;
      else score -= 4;
    }
    if (A.power === 'plug') {
      if (p.technology === 'solaire') score -= 3;
      else score += 4;
    }

    // Anti-tigre — pénaliser UV
    if (USAGE === 'tigre') {
      if (p.technology === 'uv') score -= 30;
      if (p.technology === 'co2' || p.technology === 'propane') score += 25;
      if (p.technology === 'aspirant') score += 18;
      if (p.technology === 'larvaire') score += 12;
      if (p.target_use === 'tigre') score += 15;
    }
    if (A.tigre === 'yes') {
      if (p.technology === 'uv') score -= 20;
      if (p.technology === 'co2' || p.technology === 'aspirant' || p.technology === 'propane') score += 18;
    }

    // Méthode (capture vs œufs)
    if (A.method === 'capture' && (p.technology === 'co2' || p.technology === 'aspirant' || p.technology === 'propane')) score += 12;
    if (A.method === 'eggs' && p.technology === 'larvaire') score += 18;
    if (A.method === 'both' && p.technology === 'combine') score += 15;

    // Profil chambre — kid : pénaliser pros et CO2
    if (A.profile === 'kid' && (p.target_use === 'pro' || p.technology === 'propane')) score -= 20;
    if (A.profile === 'kid' && p.technology === 'aspirant') score += 8;

    // Zone géographique anti-tigre
    if (A.zone === 'south' || A.zone === 'dom') {
      if (p.target_use === 'tigre' || p.technology === 'co2') score += 8;
    }

    // Match target_use exact = bonus marqué
    if (p.target_use === USAGE) score += 12;

    return score;
  }

  function renderResult() {
    const scored = PRODUCTS.map(p => ({ p, s: scoreProduct(p) }))
      .sort((a, b) => b.s - a.s);

    const winner = scored[0].p;
    const runners = scored.slice(1, 5).map(x => x.p);

    // Construire le pourquoi
    const why = [];
    if (winner.rating) why.push(`note ${winner.rating.toString().replace('.', ',')}/5`);
    if (winner.reviews_count) why.push(`${winner.reviews_count.toLocaleString('fr-FR')} avis`);
    if (winner.surface_m2 && answers.surface) why.push(`couvre ${winner.surface_m2} m²`);
    if (winner.surface_m2 && answers.space) why.push(`couvre ${winner.surface_m2} m²`);
    if (winner.price_eur && answers.budget) why.push(`${winner.price_eur.toString().replace('.', ',')} €`);
    if (winner.technology) {
      const techLabels = {uv:'lampe UV', co2:'CO₂', propane:'propane', aspirant:'aspirant', solaire:'solaire', larvaire:'piège à œufs', combine:'combinée'};
      why.push(`techno ${techLabels[winner.technology] || winner.technology}`);
    }
    const whyText = why.length ? `Sélectionné parce que : ${why.join(' · ')}.` : '';

    const placeholder = '<svg viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg" style="width:100%;height:160px;background:#f5f8f0;border-radius:12px;padding:20px;box-sizing:border-box"><ellipse cx="100" cy="100" rx="70" ry="80" fill="none" stroke="#1f5742" stroke-width="2"/><line x1="100" y1="40" x2="100" y2="160" stroke="#1f5742" stroke-width="2"/><circle cx="100" cy="100" r="10" fill="#c6e870"/></svg>';
    const imgHtml = winner.image_url
      ? `<img src="${winner.image_url}" alt="${winner.name}" loading="lazy">`
      : placeholder;

    const ratingHtml = winner.rating
      ? `<span class="quiz-result-rating">★ ${winner.rating.toString().replace('.', ',')}/5${winner.reviews_count ? ' · ' + winner.reviews_count.toLocaleString('fr-FR') + ' avis' : ''}</span>`
      : '';

    const metaParts = [];
    if (winner.brand) metaParts.push(`<strong>${winner.brand}</strong>`);
    if (winner.surface_m2) metaParts.push(`${winner.surface_m2} m²`);
    if (winner.price_eur) metaParts.push(`${winner.price_eur.toString().replace('.', ',')} €`);

    const runnersHtml = runners.map(r => `
      <a href="/tests/${r.slug}" class="quiz-runner">
        <div>
          <div class="quiz-runner-name">${r.name.length > 38 ? r.name.substring(0, 38) + '…' : r.name}</div>
          <div class="quiz-runner-tech">${r.technology || '—'}${r.surface_m2 ? ' · ' + r.surface_m2 + ' m²' : ''}</div>
        </div>
        <div class="quiz-runner-rate">${r.rating ? '★ ' + r.rating.toString().replace('.', ',') + '/5' : '—'}</div>
      </a>
    `).join('');

    result.style.display = 'block';
    result.innerHTML = `
      <div class="quiz-result-shell">
        <span class="quiz-result-eyebrow">Recommandation</span>
        <h2 class="quiz-result-title">${winner.name}</h2>
        <p class="quiz-result-reason">${whyText}${winner.verdict ? '<br>' + winner.verdict.replace(/^"|"$/g, '') : ''}</p>

        <div class="quiz-result-card">
          ${imgHtml}
          <div>
            <h3>${winner.brand || 'Fiche produit'}</h3>
            <div class="quiz-result-card-meta">${metaParts.join(' · ')}</div>
            ${ratingHtml}
            <div class="quiz-result-actions">
              <a href="/go/${winner.slug}" rel="nofollow sponsored" target="_blank" class="quiz-btn quiz-btn--primary">Voir sur Amazon →</a>
              <a href="/tests/${winner.slug}" class="quiz-btn quiz-btn--ghost">Lire l'analyse complète</a>
            </div>
          </div>
        </div>

        ${runners.length ? `
        <div class="quiz-result-runners">
          <h4>Alternatives à considérer</h4>
          <div class="quiz-result-runners-list">${runnersHtml}</div>
        </div>
        ` : ''}

        <div style="margin-top:32px;padding-top:24px;border-top:1px solid rgba(74,44,20,.1);font-size:13px;color:#888;">
          <a href="/quiz/${USAGE}" onclick="window.location.reload();return false;" style="color:var(--forest,#1f5742);text-decoration:none;">↻ Refaire le quiz</a>
          &nbsp;·&nbsp;
          <a href="/quiz" style="color:var(--forest,#1f5742);text-decoration:none;">Essayer un autre quiz</a>
        </div>
      </div>
    `;
    progress.style.width = '100%';
    window.scrollTo({ top: shell.offsetTop - 80, behavior: 'smooth' });
  }

  render();
})();
</script>

<?php
require __DIR__ . '/partials/footer.php';
