<?php
/**
 * ArticleGenerator — appelle l'API Anthropic (Claude) pour produire un article SEO complet
 * pour cafetiereagrain.fr et le publier directement.
 *
 * Phase 6 : prompt café + injection dynamique des produits BDD + anti-doublons 3 niveaux.
 *
 * Usage :
 *   $g = new App\Core\ArticleGenerator();
 *   $articleId = $g->generateFromQueue();           // pioche le prochain topic et publie
 *   $articleId = $g->generateFromTopic($topicArr);  // genere depuis un topic precis
 */

declare(strict_types=1);

namespace App\Core;

use PDO;
use RuntimeException;

final class ArticleGenerator
{
    private const SYSTEM_PROMPT = <<<TXT
Tu rédiges pour cafetiereagrain.fr, comparateur indépendant de cafetières à grain (machines expresso automatiques avec broyeur intégré). Monétisation via Amazon Partenaires France (tag lacasamarke08-21). Posture : analyse comparative + retours utilisateurs croisés avec fiches constructeurs. Jamais de test physique prétendu.

DOMAINE : cafetières à grain UNIQUEMENT (DeLonghi, Philips, Krups, Saeco, Jura, Sage, Melitta, Nivona, Miele, Bosch, Gaggia, Beko, etc.). Tu maîtrises le vocabulaire technique : broyeur acier conique / céramique plat, mouture (réglage 1 à 13/15 niveaux), pression (15 bars typique), pré-infusion, bypass café moulu, réservoir grains (250 à 500 g), bac à marc, buse vapeur Panarello, LatteGo (carafe 2 chambres Philips), milk carafe (DeLonghi), Latte Crema System, IFD (Intelligent Foam Device), AromaG3 (Philips), Quattro Force (Krups), thermoblock vs chaudière, niveau sonore (45-65 dB), entretien (cycle nettoyage automatique, détartrage, BRITA Intenza filtre eau).

Style :
- Tutoiement professionnel.
- Pragmatique, factuel, anti-bullshit.
- INTERDIT : "j'ai testé", "nous avons testé", "notre protocole", "achat anonyme", "test terrain", "Sud-Ouest", "atelier", "cuisine de la rédaction", "café dégusté", "arômes à la dégustation". Tu n'as JAMAIS goûté un café.
- INTERDIT : citer Amazon / Trustpilot / Avis Vérifiés (marque protégée) / forums nommément. Utilise "les utilisateurs remontent", "le consensus des retours", "la majorité des acheteurs", "les retours convergents".
- INTERDIT : "incroyable", "révolutionnaire", "indispensable", "il est crucial", "dans un monde", "à l'heure où", "plus que jamais", "disruptif", "véritable game changer".
- INTERDIT : inventer un chiffre, un pourcentage, un avis spécifique, un prix précis non fourni dans le contexte produits. Reste qualitatif si tu n'as pas la donnée.
- INTERDIT : citer un produit ou un slug qui n'est PAS dans la liste {{PRODUITS_DISPONIBLES}} fournie dans le brief utilisateur.
- INTERDIT : utiliser des URLs Amazon directes (pas de amazon.fr/dp/, pas de tag=). Toujours via /go/{slug-produit}.

Structure article :
- 1300 à 1900 mots, jamais sous 1000.
- Intro 2-3 paragraphes : un fait concret ou une question, puis la promesse de l'article et la cible.
- 4 à 6 H2 logiques. AU MOINS UN H2 reprend le mot-clé exact (ou une variante naturelle : "cafetière à grain", "machine à café avec broyeur", "broyeur intégré", "expresso automatique").
- H3 courts (5-10 mots). PAS de H1 dans content_html.
- Tableau HTML comparatif obligatoire si cluster = "comparatif" : colonnes Modèle / Broyeur / Spécialités / Prix / Note / Verdict.
- Listes à puces, mots-clés en gras (chiffres : prix, dB, bars, niveaux mouture, capacité réservoir).
- Si cluster = "test" : structure sections "Design et ergonomie", "Broyeur et mouture", "Préparation des boissons", "Entretien", "Consommation et autonomie", "Avis utilisateurs", "Verdict".

QUIZ contextuel obligatoire (clusters test/comparatif/guide) :
- Adapté au sujet PRÉCIS de l'article, pas un template générique.
- 3-4 questions, chaque question DOIT avoir un name DIFFÉRENT (q1, q2, q3, q4).
- Chaque option a data-points 1, 2 ou 3.
- 3 résultats (low/mid/high) qui RECOMMANDENT un produit de la liste {{PRODUITS_DISPONIBLES}} ou une action concrète.
- Structure :
  <div data-quiz data-results='{"low":"...recommande produit X...","mid":"...","high":"..."}'>
    <h3>Titre accroche lié au sujet</h3>
    <div data-quiz-missing>Réponds à toutes les questions avant de voir ton résultat.</div>
    <div data-quiz-step><p>1. Question contextualisée ?</p>
      <label><input type="radio" name="q1" data-points="1"> Option niveau bas</label>
      <label><input type="radio" name="q1" data-points="2"> Option intermédiaire</label>
      <label><input type="radio" name="q1" data-points="3"> Option avancée</label>
    </div>
    [...autres data-quiz-step avec name=q2/q3/q4...]
    <button data-quiz-submit>Voir ma cafetière idéale</button>
  </div>

FAQ obligatoire :
- H2 "Questions fréquentes" en fin d'article avec 4 paires H3+P. Le site génère automatiquement le JSON-LD FAQPage à partir de cette structure.
- Questions ultra-concrètes : "Combien de temps dure une cafetière à grain ?", "Faut-il acheter du café spécial ?", "Quel café choisir pour un broyeur ?", "Combien coûte l'entretien annuel ?", etc.

Conclusion :
- H2 "Notre recommandation" ou "Notre verdict" : 4-6 lignes qui tranchent par profil/usage/budget.
- Cite explicitement 1 ou 2 produits de la liste {{PRODUITS_DISPONIBLES}} avec leur slug pour le CTA final.

Liens d'affiliation :
- 2 à 4 CTA vers Amazon dans l'article. Format STRICT :
  <a href="/go/{slug-produit}?campaign={cluster}-{slug-article}" rel="noopener sponsored">Voir le prix sur Amazon</a>
- {slug-produit} doit OBLIGATOIREMENT exister dans la liste {{PRODUITS_DISPONIBLES}}. Si tu cites un produit absent de la liste, l'article sera REJETÉ.
- Place les CTA naturellement : un en milieu d'article (après la section technique), un en fin avant la conclusion, optionnellement un dans le tableau comparatif.
- N'inclus JAMAIS l'URL Amazon directe. Toujours via /go/.

Ouverture / fermeture imposée :
- Pas d'introduction "marketing" : commence par un fait, une donnée du marché, ou une question concrète.
- Termine par une recommandation claire ("Notre verdict", "Notre recommandation") qui résume en 4-6 lignes.

Réponds STRICTEMENT en JSON valide, sans markdown, sans backticks, sans texte avant/après. Schéma :
{
  "title": "string (50-65 caractères, intègre le mot-clé principal en début, sans année si l'article ne s'y prête pas)",
  "slug": "string (kebab-case, sans accent, max 80 char, sans année)",
  "description": "string (140-160 char, accroche meta-description, mention du mot-clé)",
  "content_html": "string (HTML autorisé : <p>, <h2>, <h3>, <h4>, <strong>, <em>, <ul>, <ol>, <li>, <a>, <table>, <thead>, <tbody>, <tr>, <th>, <td>, <blockquote>, <div data-quiz>. PAS de <h1>, PAS de <script>, PAS de <style>, PAS de <iframe>.)",
  "reading_time": "integer (minutes, base 200 mots/min, plancher 5)",
  "main_machines": ["slug-produit-principal"],
  "main_machines_pair": ["slug-machine-A", "slug-machine-B"]
}

main_machines = liste des 1 à 2 slugs de produits qui sont CENTRAUX dans l'article (pour anti-doublon de paire de machines). Si l'article est un comparatif "X vs Y", main_machines_pair contient les 2 slugs. Si test simple d'une machine, main_machines = [slug] et main_machines_pair = [].
TXT;

    private const ANTHROPIC_URL = 'https://api.anthropic.com/v1/messages';
    private const DEFAULT_MODEL = 'claude-sonnet-4-6';

    /** Genere depuis le prochain topic en queue puis le publie. Retourne l'article ID. */
    public function generateFromQueue(): int
    {
        $topic = Queue::pickNext();
        if (!$topic) {
            throw new RuntimeException('Queue vide.');
        }
        try {
            return $this->generateFromTopic($topic);
        } catch (\Throwable $e) {
            Queue::markFailed((int)$topic['id'], $e->getMessage());
            throw $e;
        }
    }

    /** Genere et publie depuis un topic donne. */
    public function generateFromTopic(array $topic): int
    {
        $cfg = self::loadConfig();
        $key = $cfg['anthropic_api_key'] ?? '';
        if ($key === '' || str_starts_with($key, 'sk-ant-CHANGEME')) {
            throw new RuntimeException('anthropic_api_key non configurée.');
        }

        $keyword = trim((string)($topic['keyword_target'] ?? ''));
        if ($keyword === '') {
            throw new RuntimeException('Mot-clé vide.');
        }

        // ===== Anti-doublon niveau 1 : keyword déjà traité =====
        if (Queue::isKeywordTreated($keyword)) {
            throw new RuntimeException('Mot-clé déjà publié : ' . $keyword);
        }

        // ===== Sélection produits BDD pertinents pour ce mot-clé =====
        $candidates = $this->selectProductCandidates($keyword);
        if (count($candidates) < 3) {
            throw new RuntimeException('Pas assez de produits BDD pour cet article (min 3, trouvé ' . count($candidates) . ').');
        }

        // Construction du brief avec liste produits dynamique
        $produitsBlock = $this->formatProductsForPrompt($candidates);
        $brief = sprintf(
            "Mot-clé cible : %s\nTitre suggéré : %s\nCluster : %s\nPersona : %s\n\n=== PRODUITS_DISPONIBLES (slugs valides pour /go/) ===\n%s\n\nRédige l'article en JSON selon le schéma exigé. N'invente AUCUN produit, utilise uniquement les slugs ci-dessus.",
            $keyword,
            $topic['title_hint'] ?? '',
            $topic['cluster'] ?? 'general',
            $topic['persona'] ?? 'tous',
            $produitsBlock
        );

        $data = $this->callClaude($key, $cfg['anthropic_model'] ?? self::DEFAULT_MODEL, $brief);

        // ===== Validation structurelle =====
        if (empty($data['title']) || empty($data['content_html'])) {
            throw new RuntimeException('Réponse Claude incomplète (title / content_html manquant).');
        }

        $rawSlug = (string)($data['slug'] ?? $data['title']);
        $slug = Article::slugify($rawSlug);
        $slug = $this->ensureUniqueSlug($slug ?: 'article');

        $contentHtml = (string)$data['content_html'];
        $contentText = strip_tags($contentHtml);
        $wordCount = str_word_count($contentText);
        if ($wordCount < 800) {
            throw new RuntimeException('Article trop court (' . $wordCount . ' mots, min 800).');
        }

        // ===== Validation produits cités : tous les /go/{slug} doivent exister en BDD =====
        $citedSlugs = $this->extractCitedSlugs($contentHtml);
        $validSlugs = array_column($candidates, 'slug');
        $invalid = array_diff($citedSlugs, $validSlugs);
        // Anti-tolérance : on tolère 0 invalide. Si Claude a halluciné, fail.
        if (!empty($invalid)) {
            throw new RuntimeException('Produits cités invalides (hallucinations) : ' . implode(', ', $invalid));
        }
        if (count($citedSlugs) < 1) {
            throw new RuntimeException('Aucun CTA produit /go/ détecté dans l\'article.');
        }

        // ===== Anti-doublon niveau 2 : signature sémantique du sujet =====
        $titleHash = self::hashTopic((string)$data['title'], $slug);
        if ($this->isTitleHashDuplicate($titleHash)) {
            throw new RuntimeException('Sujet trop proche d\'un article existant (hash collision).');
        }

        // ===== Anti-doublon niveau 3 : paire de machines principales =====
        $mainMachines = array_values(array_filter((array)($data['main_machines'] ?? []), 'is_string'));
        $mainPair = array_values(array_filter((array)($data['main_machines_pair'] ?? []), 'is_string'));

        // Convertir slugs → ASINs
        $asinPair = $this->slugsToAsins($mainPair);
        if (!empty($asinPair) && count($asinPair) === 2) {
            sort($asinPair); // ordre alphabétique pour clé unique
            if ($this->isMachinePairTaken($asinPair[0], $asinPair[1])) {
                throw new RuntimeException('Paire de machines déjà couverte : ' . implode(' vs ', $mainPair));
            }
        }

        // ===== Insertion article =====
        $row = [
            'slug'           => $slug,
            'title'          => mb_substr((string)$data['title'], 0, 255),
            'description'    => mb_substr((string)($data['description'] ?? ''), 0, 500),
            'keyword_target' => mb_substr($keyword, 0, 150),
            'cluster'        => self::sanitizeCluster((string)($topic['cluster'] ?? 'general')),
            'persona'        => self::sanitizePersona((string)($topic['persona'] ?? 'tous')),
            'content_html'   => $contentHtml,
            'featured_image' => null,
            'reading_time'   => max(5, min(60, (int)($data['reading_time'] ?? max(5, (int)($wordCount / 200))))),
            'status'         => 'published',
            'publish_at'     => date('Y-m-d H:i:s'),
            'author_id'      => null,
            'title_hash'     => $titleHash,
        ];

        $articleId = self::createArticleWithHash($row);

        // Lier produits cités à l'article (article_products)
        $this->linkArticleProducts($articleId, $citedSlugs, $mainMachines);

        // Enregistrer la paire de machines (anti-doublon niveau 3)
        if (count($asinPair) === 2) {
            $this->registerMachinePair($articleId, $asinPair[0], $asinPair[1], 'versus');
        } elseif (count($mainMachines) === 1) {
            $singleAsin = $this->slugsToAsins($mainMachines);
            if (!empty($singleAsin)) {
                $this->registerMachinePair($articleId, $singleAsin[0], null, 'single');
            }
        }

        // Marquer la queue comme done
        if (!empty($topic['id'])) {
            Queue::markDone((int)$topic['id'], $articleId);
        }

        // Tracking conso API
        self::trackApiUsage('anthropic', 0.12);

        return $articleId;
    }

    /**
     * Sélectionne les produits BDD pertinents pour un mot-clé donné.
     * Stratégie : matching sur brand/name dans le keyword, fallback sur top score_pertinence.
     */
    private function selectProductCandidates(string $keyword): array
    {
        $pdo = Database::pdo();
        $kwLower = mb_strtolower($keyword, 'UTF-8');

        // 1) Détecter les marques mentionnées dans le keyword
        $brands = ['delonghi','de\'longhi','philips','krups','saeco','jura','sage','melitta','nivona','miele','bosch','gaggia','beko','yoer'];
        $matchedBrands = [];
        foreach ($brands as $b) {
            if (str_contains($kwLower, str_replace('\'', '', $b))) {
                $matchedBrands[] = $b;
            }
        }

        $candidates = [];

        // 2) Si marques détectées, charger les produits de ces marques
        if (!empty($matchedBrands)) {
            $placeholders = implode(',', array_fill(0, count($matchedBrands), '?'));
            $sql = "SELECT id, asin, slug, name, brand, price_eur, rating, ratings_total, verdict
                    FROM products
                    WHERE status = 'published'
                      AND LOWER(brand) IN ($placeholders)
                    ORDER BY score_pertinence DESC LIMIT 8";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($matchedBrands);
            $candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        // 3) Compléter avec top produits si pas assez (toujours min 6 pour donner du choix à Claude)
        $needed = 6 - count($candidates);
        if ($needed > 0) {
            $existingIds = array_column($candidates, 'id') ?: [0];
            $placeholders = implode(',', array_fill(0, count($existingIds), '?'));
            $sql = "SELECT id, asin, slug, name, brand, price_eur, rating, ratings_total, verdict
                    FROM products
                    WHERE status = 'published' AND id NOT IN ($placeholders)
                    ORDER BY score_pertinence DESC LIMIT " . (int)$needed;
            $stmt = $pdo->prepare($sql);
            $stmt->execute($existingIds);
            $more = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $candidates = array_merge($candidates, $more);
        }

        return $candidates;
    }

    /** Formatte la liste produits pour injection dans le prompt user. */
    private function formatProductsForPrompt(array $products): string
    {
        $lines = [];
        foreach ($products as $p) {
            $lines[] = sprintf(
                "- %s (slug=%s, brand=%s, prix=%s€, note=%s/5 sur %s avis) — %s",
                $p['name'],
                $p['slug'],
                $p['brand'] ?? '?',
                $p['price_eur'] ?? '?',
                $p['rating'] ?? '?',
                $p['ratings_total'] ?? '?',
                mb_substr((string)($p['verdict'] ?? ''), 0, 200)
            );
        }
        return implode("\n", $lines);
    }

    /** Extrait tous les slugs cités via /go/{slug}? dans le HTML. */
    private function extractCitedSlugs(string $html): array
    {
        if (preg_match_all('#/go/([a-z0-9\-]+)(?:\?|"|\s|/)#i', $html, $m)) {
            return array_values(array_unique(array_map('strtolower', $m[1])));
        }
        return [];
    }

    /** Convertit une liste de slugs produits en ASINs (filtre les inconnus). */
    private function slugsToAsins(array $slugs): array
    {
        if (empty($slugs)) return [];
        $placeholders = implode(',', array_fill(0, count($slugs), '?'));
        $stmt = Database::pdo()->prepare("SELECT asin FROM products WHERE slug IN ($placeholders) AND asin IS NOT NULL");
        $stmt->execute(array_map('strtolower', $slugs));
        return array_filter(array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'asin'));
    }

    /** Vrai si un article publié a déjà ce title_hash (duplicate sémantique). */
    private function isTitleHashDuplicate(string $hash): bool
    {
        $stmt = Database::pdo()->prepare("SELECT COUNT(*) FROM articles WHERE title_hash = ?");
        $stmt->execute([$hash]);
        return (int)$stmt->fetchColumn() > 0;
    }

    /** Vrai si la paire (asin_a, asin_b) triée existe déjà dans article_machines_main. */
    private function isMachinePairTaken(string $asinA, string $asinB): bool
    {
        $stmt = Database::pdo()->prepare(
            "SELECT COUNT(*) FROM article_machines_main WHERE asin_a = ? AND asin_b = ?"
        );
        $stmt->execute([$asinA, $asinB]);
        return (int)$stmt->fetchColumn() > 0;
    }

    /** Insère une nouvelle paire de machines comme principale d'un article. */
    private function registerMachinePair(int $articleId, string $asinA, ?string $asinB, string $type): void
    {
        $stmt = Database::pdo()->prepare(
            "INSERT IGNORE INTO article_machines_main (article_id, asin_a, asin_b, pair_type) VALUES (?, ?, ?, ?)"
        );
        $stmt->execute([$articleId, $asinA, $asinB, $type]);
    }

    /** Lie un article aux produits cités (table article_products). */
    private function linkArticleProducts(int $articleId, array $citedSlugs, array $mainMachines): void
    {
        if (empty($citedSlugs)) return;
        $placeholders = implode(',', array_fill(0, count($citedSlugs), '?'));
        $stmt = Database::pdo()->prepare("SELECT id, slug FROM products WHERE slug IN ($placeholders)");
        $stmt->execute($citedSlugs);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $mainSet = array_flip(array_map('strtolower', $mainMachines));
        $insert = Database::pdo()->prepare(
            "INSERT IGNORE INTO article_products (article_id, product_id, position) VALUES (?, ?, ?)"
        );
        foreach ($products as $p) {
            $position = isset($mainSet[$p['slug']]) ? 1 : 2;
            $insert->execute([$articleId, $p['id'], $position]);
        }
    }

    /** Hash sémantique : SHA1 du slug normalisé sans stopwords ni connecteurs. */
    public static function hashTopic(string $title, string $slug): string
    {
        $base = strtolower($slug);
        $base = preg_replace('/-(vs|de|le|la|les|du|des|et|ou|au|aux|pour|sur|sans|avec|en)-/', '-', $base) ?? $base;
        $base = preg_replace('/-(2024|2025|2026|2027)$/', '', $base) ?? $base;
        return sha1($base);
    }

    private function callClaude(string $apiKey, string $model, string $userPrompt): array
    {
        $payload = [
            'model'      => $model,
            'max_tokens' => 8000,
            'system'     => self::SYSTEM_PROMPT,
            'messages'   => [[
                'role'    => 'user',
                'content' => $userPrompt,
            ]],
        ];

        $ch = curl_init(self::ANTHROPIC_URL);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'x-api-key: ' . $apiKey,
                'anthropic-version: 2023-06-01',
            ],
            CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            CURLOPT_TIMEOUT        => 240,
        ]);
        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($body === false) {
            throw new RuntimeException('cURL : ' . $err);
        }
        if ($code !== 200) {
            throw new RuntimeException('API HTTP ' . $code . ' : ' . mb_substr((string)$body, 0, 400));
        }

        $resp = json_decode((string)$body, true);
        if (!is_array($resp) || empty($resp['content'][0]['text'])) {
            throw new RuntimeException('Réponse Anthropic non parseable.');
        }

        $text = trim((string)$resp['content'][0]['text']);
        // Nettoyer ```json éventuels
        $text = preg_replace('/^```(?:json)?\s*|\s*```$/m', '', $text) ?: $text;

        $data = json_decode($text, true);
        if (!is_array($data)) {
            throw new RuntimeException('Sortie JSON invalide : ' . mb_substr($text, 0, 300));
        }
        return $data;
    }

    private function ensureUniqueSlug(string $base): string
    {
        $slug = $base;
        $i = 2;
        while (Queue::isSlugTaken($slug)) {
            $slug = $base . '-' . $i;
            $i++;
            if ($i > 50) break;
        }
        return $slug;
    }

    /**
     * Insert article + title_hash (extension de Article::create avec colonne title_hash).
     */
    private static function createArticleWithHash(array $data): int
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare(
            'INSERT INTO articles (slug, title, description, keyword_target, title_hash, cluster, persona,
                                   content_html, featured_image, reading_time, status, publish_at, author_id)
             VALUES (:slug, :title, :description, :keyword_target, :title_hash, :cluster, :persona,
                     :content_html, :featured_image, :reading_time, :status, :publish_at, :author_id)'
        );
        $stmt->execute([
            ':slug'           => $data['slug'],
            ':title'          => $data['title'],
            ':description'    => $data['description'],
            ':keyword_target' => $data['keyword_target'],
            ':title_hash'     => $data['title_hash'],
            ':cluster'        => $data['cluster'] ?? 'general',
            ':persona'        => $data['persona'] ?? 'tous',
            ':content_html'   => $data['content_html'],
            ':featured_image' => $data['featured_image'] ?? null,
            ':reading_time'   => (int)($data['reading_time'] ?? 5),
            ':status'         => $data['status'] ?? 'draft',
            ':publish_at'     => $data['publish_at'] ?? null,
            ':author_id'      => $data['author_id'] ?? null,
        ]);
        return (int)$pdo->lastInsertId();
    }

    private static function sanitizeCluster(string $c): string
    {
        $allowed = ['comparatif','guide','test','saison','usage','general'];
        return in_array($c, $allowed, true) ? $c : 'general';
    }

    private static function sanitizePersona(string $p): string
    {
        $allowed = ['tous','particulier','jardinier','pro','restaurateur'];
        return in_array($p, $allowed, true) ? $p : 'tous';
    }

    /**
     * Tracking de la consommation API (Anthropic, Rainforest, Mangools).
     * Inserte ou incrémente la ligne du mois courant dans api_usage.
     */
    public static function trackApiUsage(string $apiName, float $costEur = 0.0): void
    {
        try {
            $pdo = Database::pdo();
            $stmt = $pdo->prepare(
                "INSERT INTO api_usage (api_name, period_date, calls, cost_eur, last_call_at)
                 VALUES (?, DATE_FORMAT(CURDATE(), '%Y-%m-01'), 1, ?, NOW())
                 ON DUPLICATE KEY UPDATE calls = calls + 1, cost_eur = cost_eur + VALUES(cost_eur), last_call_at = NOW()"
            );
            $stmt->execute([$apiName, $costEur]);
        } catch (\Throwable $e) {
            // Silencieux : tracking non bloquant
        }
    }

    private static function loadConfig(): array
    {
        $configFile  = dirname(__DIR__, 2) . '/config/config.php';
        $exampleFile = dirname(__DIR__, 2) . '/config/config.example.php';
        if (is_file($configFile)) return require $configFile;
        if (is_file($exampleFile)) return require $exampleFile;
        return [];
    }
}
