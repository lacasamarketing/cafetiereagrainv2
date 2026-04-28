<?php
/**
 * ArticleGenerator — appelle l'API Anthropic (Claude) pour produire un article SEO complet
 * pour cafetiereagrain.fr et le publier directement.
 *
 * Usage :
 *   $g = new App\Core\ArticleGenerator();
 *   $articleId = $g->generateFromQueue();           // pioche le prochain topic et publie
 *   $articleId = $g->generateFromTopic($topicArr);  // genere depuis un topic precis
 */

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

final class ArticleGenerator
{
    private const SYSTEM_PROMPT = <<<TXT
Tu rediges pour cafetiereagrain.fr, comparateur independant de cafetieres a grain. Monetise via Amazon Partenaires France (tag lacasamarke08-21). Posture : analyse comparative + retours utilisateurs croises avec fiches constructeurs. Jamais de test physique pretendu.

Style :
- Tutoiement professionnel.
- Pragmatique, factuel, anti-bullshit.
- INTERDIT : "j'ai teste", "nous avons teste", "notre protocole", "achat anonyme", "test terrain", "Sud-Ouest", "jardin 200 m2", "pesee", "grammes captures".
- INTERDIT : citer Amazon / Trustpilot / Avis Verifies (marque protegee) / forums nommement. Utilise "les utilisateurs remontent", "le consensus des retours", "la majorite des acheteurs".
- INTERDIT : "incroyable", "revolutionnaire", "indispensable", "il est crucial", "dans un monde", "a l'heure ou", "plus que jamais", "disruptif".
- N'invente JAMAIS un chiffre, un pourcentage, un avis. Qualitatif si pas de donnee.
- Cite uniquement les 6 produits suivants quand pertinent (ce sont les seuls slugs valides en BDD pour /go/) :
  * mosquito-magnet-pioneer (CO2, 3000 m2, 799 EUR, 3,4/5)
  * biogents-bg-mosquitaire (aspirant, 200 m2, 139 EUR, 3,6/5, 2076 avis)
  * hexa-favex-hexasafe (interieur silencieux, 40 m2, 60 EUR, recent, pas encore d'avis)
  * yissvic-uv-led-rechargeable (UV nomade, 100 m2, 40 EUR, 4,4/5)
  * biogents-bg-gat (anti-tigre piege a ponte, lot de 2, 50 m2, 59 EUR, 3,5/5)
  * lampe-led-café en grain-usb (UV USB, 60 m2, 33 EUR, 4,9/5)
- Liens d'affiliation TOUJOURS via /go/{slug}?campaign={slug-article} (cloaking, jamais d'URL Amazon directe). Insere 2 a 4 CTA naturels.

Structure :
- 1200 a 1800 mots, jamais sous 1000.
- Intro 2-3 paragraphes (hook + promesse + cible).
- 4 a 6 H2 logiques. AU MOINS UN H2 reprend le mot-cle exact (ou variante "piege a moustique(s)", "piege café en grain", etc.).
- H3 courts (5-10 mots). Pas de H1 dans content_html.
- Tableau HTML comparatif si comparatif (Modele / Technologie / Surface / Prix / Verdict).
- Listes a puces, mots-cles en gras (chiffres, surfaces, prix, dB).

QUIZ contextuel obligatoire (pour clusters test/comparatif/guide/diy) :
- Adapte au sujet PRECIS de l'article, pas un template generique.
- 3-4 questions, chaque question DOIT avoir un name DIFFERENT (q1, q2, q3, q4) sinon le calcul de score plante.
- Chaque option a data-points 1, 2 ou 3.
- 3 resultats (low/mid/high) qui RECOMMANDENT un produit ou une action concrete liee au sujet.
- Structure :
  <div data-quiz data-results='{"low":"...","mid":"...","high":"..."}'>
    <h3>Titre accroche lie au sujet</h3>
    <div data-quiz-missing>Reponds a toutes les questions avant de voir ton resultat.</div>
    <div data-quiz-step><p>1. Question contextualisee ?</p>
      <label><input type="radio" name="q1" data-points="1"> Option niveau bas</label>
      <label><input type="radio" name="q1" data-points="2"> Option niveau intermediaire</label>
      <label><input type="radio" name="q1" data-points="3"> Option niveau avance</label>
    </div>
    [...autres data-quiz-step avec name=q2/q3/q4...]
    <button data-quiz-submit>Voir mon resultat</button>
  </div>

FAQ obligatoire :
- H2 "Questions frequentes" en fin d'article avec 4 paires H3+P (le site genere automatiquement le JSON-LD FAQPage).

Conclusion :
- H2 "Notre recommandation" ou "Notre verdict" : 3-5 lignes qui tranchent par profil/usage.

Reponds STRICTEMENT en JSON valide, sans markdown, sans backticks, sans texte avant/apres :
{
  "title": "string 50-65 char, mot-cle en tete",
  "slug": "kebab-case sans accent max 80 char",
  "description": "meta 140-160 char",
  "content_html": "HTML sans H1, autorisé : p, h2, h3, h4, ul, ol, li, strong, em, a, table, thead, tbody, tr, th, td, blockquote, div (quiz uniquement). PAS de script, PAS de style.",
  "reading_time": integer (mots/200, plancher 5)
}

Liens d'affiliation Amazon :
- Insere 2 a 4 CTA vers Amazon dans l'article. Format obligatoire :
  <a href="/go/{slug-produit}?campaign={cluster}-{slug-article}" rel="noopener sponsored">Voir le prix sur Amazon</a>
- {slug-produit} = un slug court qui identifie le produit recommande (ex: mosquito-magnet-patriot, biogents-bg-mosquitaire). Le mappage ASIN se gere a part en BDD (table settings).
- Place ces CTA naturellement : un en milieu d'article (apres la section technique), un en fin avant la conclusion.
- N'inclus jamais l'URL Amazon directe : on passe TOUJOURS par /go/.

Ouverture / fermeture imposee :
- Pas d'introduction "marketing" : commence par un fait, une observation ou une question concrete.
- Termine par une recommandation claire ("Notre verdict", "Notre recommandation") qui resume en 3-4 lignes.

Reponds STRICTEMENT en JSON valide, sans markdown, sans backticks, sans texte avant/apres. Schema :
{
  "title": "string (50-65 char, integre le mot-cle principal, sans annee si l'article ne s'y prete pas)",
  "slug": "string (kebab-case, sans accent, max 80 char, sans annee)",
  "description": "string (140-160 char, accroche meta-description)",
  "content_html": "string (HTML : <p>, <h2>, <h3>, <strong>, <em>, <ul>, <ol>, <li>, <a>, <table>, <thead>, <tbody>, <tr>, <th>, <td>, <blockquote>. Pas de <h1>, pas de <script>, pas de <style>.)",
  "reading_time": "integer (minutes, base 200 mots/min, plancher 4)"
}
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

    /** Genere et publie depuis un topic donne (array avec keys : id, keyword_target, title_hint, cluster, persona). */
    public function generateFromTopic(array $topic): int
    {
        $cfg = self::loadConfig();
        $key = $cfg['anthropic_api_key'] ?? '';
        if ($key === '' || str_starts_with($key, 'sk-ant-CHANGEME')) {
            throw new RuntimeException('anthropic_api_key non configuree.');
        }

        $brief = sprintf(
            "Mot-cle cible : %s\nTitre suggere : %s\nCluster : %s\nPersona : %s\n\nRedige l'article en JSON selon le schema exige.",
            $topic['keyword_target'] ?? '',
            $topic['title_hint'] ?? '',
            $topic['cluster'] ?? 'general',
            $topic['persona'] ?? 'tous'
        );

        $data = $this->callClaude($key, $cfg['anthropic_model'] ?? self::DEFAULT_MODEL, $brief);

        // Validation
        if (empty($data['title']) || empty($data['content_html'])) {
            throw new RuntimeException('Reponse Claude incomplete (title / content_html manquant).');
        }

        // Slug : prefere celui donne par l'IA, fallback sur slugify(title)
        $rawSlug = $data['slug'] ?? $data['title'];
        $slug = Article::slugify((string)$rawSlug);
        $slug = $this->ensureUniqueSlug($slug ?: 'article');

        // Anti-cannibalisation par mot-cle (on bloque si le keyword exact a deja un article)
        $keyword = (string)($topic['keyword_target'] ?? '');
        if ($keyword !== '' && Queue::isKeywordTreated($keyword)) {
            throw new RuntimeException('Mot-cle deja traite : ' . $keyword);
        }

        $contentText = strip_tags((string)$data['content_html']);
        $wordCount = str_word_count($contentText);
        if ($wordCount < 800) {
            throw new RuntimeException('Article trop court (' . $wordCount . ' mots).');
        }

        $row = [
            'slug'           => $slug,
            'title'          => mb_substr((string)$data['title'], 0, 255),
            'description'    => mb_substr((string)($data['description'] ?? ''), 0, 500),
            'keyword_target' => mb_substr($keyword, 0, 150),
            'cluster'        => in_array($topic['cluster'] ?? '', ['comparatif','guide','test','saison','usage','general'], true) ? $topic['cluster'] : 'general',
            'persona'        => in_array($topic['persona'] ?? '', ['tous','particulier','jardinier','pro','restaurateur'], true) ? $topic['persona'] : 'tous',
            'content_html'   => (string)$data['content_html'],
            'featured_image' => null,
            'reading_time'   => max(4, min(60, (int)($data['reading_time'] ?? max(5, (int)($wordCount / 200))))),
            'status'         => 'published',
            'publish_at'     => date('Y-m-d H:i:s'),
            'author_id'      => null,
        ];

        $articleId = Article::create($row);

        if (!empty($topic['id'])) {
            Queue::markDone((int)$topic['id'], $articleId);
        }

        return $articleId;
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
            throw new RuntimeException('Reponse Anthropic non parseable.');
        }

        $text = trim((string)$resp['content'][0]['text']);
        // Defense : nettoyer ```json eventuels
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

    private static function loadConfig(): array
    {
        $configFile  = dirname(__DIR__, 2) . '/config/config.php';
        $exampleFile = dirname(__DIR__, 2) . '/config/config.example.php';
        if (is_file($configFile)) return require $configFile;
        if (is_file($exampleFile)) return require $exampleFile;
        return [];
    }
}
