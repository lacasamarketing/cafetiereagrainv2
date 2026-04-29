<?php
/**
 * Client Mangools KWFinder API
 *
 * Doc : https://mangools.com/api/
 * Auth : header X-Access-Token (clé API depuis config)
 *
 * Économies intégrées :
 *  - Cache local 90 jours (table mangools_cache) : un même keyword n'appelle l'API qu'une fois par trimestre
 *  - Quota mensuel par projet (config.mangools.monthly_budget) : kill-switch automatique
 *  - Filtres opportunité côté client (volume / KD / trend) avant push en queue
 *
 * Usage :
 *   $m = new App\Core\MangoolsClient();
 *   $kw = $m->searchByKeyword('cafetière à grain');
 *   $related = $m->relatedKeywords('cafetière à grain');
 *
 * Réponse normalisée : ['keyword' => string, 'volume' => int, 'kd' => int, 'cpc' => float, 'trend' => array<int>]
 */

declare(strict_types=1);

namespace App\Core;

use PDO;
use RuntimeException;

final class MangoolsClient
{
    private const BASE_URL = 'https://api.mangools.com/v3';
    private const CACHE_TTL_DAYS = 90;
    private const DEFAULT_LOCATION = 'fr';
    private const DEFAULT_LANGUAGE = 'fr';

    private string $apiKey;
    private int $monthlyBudget;
    private bool $enabled;

    public function __construct()
    {
        $cfg = self::loadConfig();
        $mango = $cfg['mangools'] ?? [];
        $this->apiKey = (string)($mango['api_key'] ?? '');
        $this->monthlyBudget = (int)($mango['monthly_budget'] ?? 10);
        $this->enabled = (bool)($mango['enabled'] ?? true);

        if (!$this->enabled) {
            throw new RuntimeException('Mangools désactivé via config.mangools.enabled.');
        }
        if ($this->apiKey === '' || str_starts_with($this->apiKey, 'CHANGE')) {
            throw new RuntimeException('Mangools API key non configurée (config.mangools.api_key).');
        }
    }

    /**
     * Recherche un keyword exact : volume, KD, CPC, trend 12 mois.
     *
     * @return array{keyword:string,volume:int,kd:int,cpc:float,trend:array<int>}|null null si rien trouvé
     */
    public function searchByKeyword(string $keyword, ?string $location = null, ?string $language = null): ?array
    {
        $keyword = trim($keyword);
        if ($keyword === '') return null;

        $cached = $this->getCached($keyword);
        if ($cached !== null) {
            return $this->normalizeResponse($keyword, $cached);
        }

        $this->ensureBudgetAvailable();

        $params = [
            'kw' => $keyword,
            'location' => $location ?? self::DEFAULT_LOCATION,
            'language' => $language ?? self::DEFAULT_LANGUAGE,
        ];

        $resp = $this->httpGet('/kwfinder/search-by-keyword', $params);
        $this->setCached($keyword, $resp);
        ArticleGenerator::trackApiUsage('mangools', 0.0);

        return $this->normalizeResponse($keyword, $resp);
    }

    /**
     * Récupère les keywords liés (related). Renvoie une liste filtrée par opportunité.
     *
     * @return array<int, array{keyword:string,volume:int,kd:int,cpc:float}>
     */
    public function relatedKeywords(string $seed, int $maxKd = 30, int $minVolume = 150): array
    {
        $cached = $this->getCached('related:' . $seed);
        if ($cached !== null) {
            return $this->filterOpportunities($cached, $maxKd, $minVolume);
        }

        $this->ensureBudgetAvailable();

        $params = [
            'kw' => $seed,
            'location' => self::DEFAULT_LOCATION,
            'language' => self::DEFAULT_LANGUAGE,
        ];

        $resp = $this->httpGet('/kwfinder/related-keywords', $params);
        $this->setCached('related:' . $seed, $resp);
        ArticleGenerator::trackApiUsage('mangools', 0.0);

        return $this->filterOpportunities($resp, $maxKd, $minVolume);
    }

    /**
     * Autocomplete keywords (suggestions Google).
     *
     * @return array<int, string>
     */
    public function autocompleteKeywords(string $seed): array
    {
        $cached = $this->getCached('autocomplete:' . $seed);
        if ($cached !== null) {
            return is_array($cached['keywords'] ?? null) ? array_column($cached['keywords'], 'kw') : [];
        }

        $this->ensureBudgetAvailable();

        $params = [
            'kw' => $seed,
            'location' => self::DEFAULT_LOCATION,
            'language' => self::DEFAULT_LANGUAGE,
        ];

        $resp = $this->httpGet('/kwfinder/autocomplete-keywords', $params);
        $this->setCached('autocomplete:' . $seed, $resp);
        ArticleGenerator::trackApiUsage('mangools', 0.0);

        return is_array($resp['keywords'] ?? null) ? array_column($resp['keywords'], 'kw') : [];
    }

    /**
     * Stats API : crédits utilisés ce mois pour cafetiereagrain.
     */
    public function getMonthUsage(): int
    {
        $stmt = Database::pdo()->prepare(
            "SELECT calls FROM api_usage
             WHERE api_name = 'mangools' AND period_date = DATE_FORMAT(CURDATE(), '%Y-%m-01')"
        );
        $stmt->execute();
        return (int)($stmt->fetchColumn() ?: 0);
    }

    public function getRemainingBudget(): int
    {
        return max(0, $this->monthlyBudget - $this->getMonthUsage());
    }

    // ============= INTERNALS =============

    private function ensureBudgetAvailable(): void
    {
        if ($this->getMonthUsage() >= $this->monthlyBudget) {
            throw new RuntimeException(sprintf(
                'Budget Mangools mensuel atteint (%d/%d). Reset au 1er du mois ou bump config.mangools.monthly_budget.',
                $this->getMonthUsage(),
                $this->monthlyBudget
            ));
        }
    }

    /**
     * Normalise une réponse search-by-keyword.
     */
    private function normalizeResponse(string $keyword, array $resp): ?array
    {
        // KWFinder renvoie typiquement : kw, search_volume, difficulty, cpc, trend (12 valeurs)
        $kw = $resp['kw'] ?? $resp['keyword'] ?? $keyword;
        if (!isset($resp['search_volume']) && !isset($resp['volume'])) {
            return null;
        }
        return [
            'keyword' => (string)$kw,
            'volume'  => (int)($resp['search_volume'] ?? $resp['volume'] ?? 0),
            'kd'      => (int)($resp['difficulty'] ?? $resp['kd'] ?? 0),
            'cpc'     => (float)($resp['cpc'] ?? 0),
            'trend'   => array_map('intval', (array)($resp['trend'] ?? [])),
        ];
    }

    /**
     * Filtre les related/autocomplete par opportunité (volume mini + KD maxi).
     */
    private function filterOpportunities(array $resp, int $maxKd, int $minVolume): array
    {
        $keywords = $resp['keywords'] ?? $resp['related_keywords'] ?? [];
        if (!is_array($keywords)) return [];

        $out = [];
        foreach ($keywords as $kw) {
            $vol = (int)($kw['search_volume'] ?? $kw['volume'] ?? 0);
            $kd = (int)($kw['difficulty'] ?? $kw['kd'] ?? 0);
            if ($vol < $minVolume) continue;
            if ($kd > $maxKd) continue;
            $out[] = [
                'keyword' => (string)($kw['kw'] ?? $kw['keyword'] ?? ''),
                'volume'  => $vol,
                'kd'      => $kd,
                'cpc'     => (float)($kw['cpc'] ?? 0),
            ];
        }
        // Tri par opportunité (volume / max(kd,1))
        usort($out, fn($a, $b) => ($b['volume'] / max($b['kd'], 1)) <=> ($a['volume'] / max($a['kd'], 1)));
        return $out;
    }

    /**
     * Cache local : récupère une réponse < 90 jours.
     */
    private function getCached(string $cacheKey): ?array
    {
        $key = self::normalizeKey($cacheKey);
        $stmt = Database::pdo()->prepare(
            "SELECT response_json FROM mangools_cache
             WHERE keyword_norm = ? AND fetched_at > DATE_SUB(NOW(), INTERVAL ? DAY) LIMIT 1"
        );
        $stmt->execute([$key, self::CACHE_TTL_DAYS]);
        $row = $stmt->fetchColumn();
        if (!$row) return null;
        $data = json_decode((string)$row, true);
        return is_array($data) ? $data : null;
    }

    /**
     * Cache local : enregistre une réponse pour 90 jours.
     */
    private function setCached(string $cacheKey, array $data): void
    {
        $key = self::normalizeKey($cacheKey);
        $stmt = Database::pdo()->prepare(
            "INSERT INTO mangools_cache (keyword_norm, response_json, fetched_at)
             VALUES (?, ?, NOW())
             ON DUPLICATE KEY UPDATE response_json = VALUES(response_json), fetched_at = NOW()"
        );
        $stmt->execute([$key, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);
    }

    public static function normalizeKey(string $s): string
    {
        $s = mb_strtolower(trim($s), 'UTF-8');
        $s = strtr($s, [
            'à' => 'a', 'â' => 'a', 'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
            'î' => 'i', 'ï' => 'i', 'ô' => 'o', 'ö' => 'o', 'ù' => 'u', 'û' => 'u',
            'ü' => 'u', 'ç' => 'c', 'ñ' => 'n',
        ]);
        $s = preg_replace('/\s+/', ' ', $s) ?? $s;
        return mb_substr($s, 0, 200);
    }

    private function httpGet(string $path, array $params): array
    {
        $url = self::BASE_URL . $path . '?' . http_build_query($params);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 60,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_HTTPHEADER     => [
                'X-Access-Token: ' . $this->apiKey,
                'Accept: application/json',
            ],
            CURLOPT_USERAGENT      => 'cafetiereagrain.fr / MangoolsClient PHP',
        ]);
        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($body === false) {
            throw new RuntimeException('Mangools cURL : ' . $err);
        }
        if ($code !== 200) {
            throw new RuntimeException('Mangools HTTP ' . $code . ' : ' . mb_substr((string)$body, 0, 300));
        }
        $json = json_decode((string)$body, true);
        if (!is_array($json)) {
            throw new RuntimeException('Mangools : réponse non JSON.');
        }
        if (!empty($json['error'])) {
            throw new RuntimeException('Mangools error : ' . (string)$json['error']);
        }
        return $json;
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
