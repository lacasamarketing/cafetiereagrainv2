<?php
/**
 * Client Rainforest API (https://www.rainforestapi.com/)
 *
 * Mutualisable entre tous les sites La Casa Marketing (cafetiereagrain.fr,
 * cafetiereagrain.fr, etc.) — il suffit de copier ce fichier et de définir
 * la clé API + le domaine de destination dans config.php.
 *
 * Usage minimal :
 *   $rf = new App\Core\RainforestClient();
 *   $products = $rf->searchProducts('cafetière à grain');
 *   $product = $rf->getProduct('B0CR1WSBRH');
 *   $offers = $rf->getOffers('B0CR1WSBRH');
 *
 * Doc API : https://docs.trajectdata.com/rainforestapi
 */

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

final class RainforestClient
{
    private const ENDPOINT = 'https://api.rainforestapi.com/request';
    private const DEFAULT_DOMAIN = 'amazon.fr';

    private string $apiKey;
    private string $defaultDomain;
    private string $destination;

    /**
     * @param string|null $apiKey       Clé API Rainforest (sinon lue dans config)
     * @param string      $domain       Domaine Amazon par défaut (amazon.fr, amazon.com, etc.)
     * @param string      $destination  Tag de tracking (ex: cafetiereagrain.fr) pour facturation par site
     */
    public function __construct(?string $apiKey = null, string $domain = self::DEFAULT_DOMAIN, string $destination = '')
    {
        $cfg = self::loadConfig();
        $this->apiKey = $apiKey ?: ($cfg['rainforest_api_key'] ?? '');
        if ($this->apiKey === '' || str_starts_with($this->apiKey, 'CHANGE')) {
            throw new RuntimeException('Rainforest API key non configurée (config/config.php → rainforest_api_key).');
        }
        $this->defaultDomain = $domain;
        $this->destination = $destination ?: ($cfg['base_url'] ?? '');
    }

    /**
     * Recherche Amazon par mot-clé. Retourne le tableau search_results.
     *
     * @param array $extra  options additionnelles (sort_by, category_id, etc.)
     * @return array<int, array> liste de produits avec asin, title, image, price, rating
     */
    public function searchProducts(string $keyword, ?string $domain = null, array $extra = []): array
    {
        $params = array_merge([
            'type' => 'search',
            'amazon_domain' => $domain ?? $this->defaultDomain,
            'search_term' => $keyword,
            'language' => 'fr_FR',
        ], $extra);

        $resp = $this->call($params);
        return $resp['search_results'] ?? [];
    }

    /**
     * Récupère la fiche complète d'un produit par ASIN.
     *
     * @return array     données produit (title, brand, main_image, price, rating, features, etc.)
     */
    public function getProduct(string $asin, ?string $domain = null): array
    {
        $params = [
            'type' => 'product',
            'amazon_domain' => $domain ?? $this->defaultDomain,
            'asin' => $asin,
            'language' => 'fr_FR',
        ];

        $resp = $this->call($params);
        return $resp['product'] ?? [];
    }

    /**
     * Récupère uniquement les offres/prix d'un ASIN (plus léger que getProduct).
     */
    public function getOffers(string $asin, ?string $domain = null): array
    {
        $params = [
            'type' => 'offers',
            'amazon_domain' => $domain ?? $this->defaultDomain,
            'asin' => $asin,
            'language' => 'fr_FR',
        ];

        $resp = $this->call($params);
        return $resp['offers'] ?? [];
    }

    /**
     * Crédits restants sur le compte (debug / monitoring).
     */
    public function getAccountInfo(): array
    {
        $url = self::ENDPOINT . '?api_key=' . urlencode($this->apiKey) . '&type=account';
        $body = $this->httpGet($url);
        $json = json_decode($body, true);
        if (!is_array($json)) {
            throw new RuntimeException('Réponse non parseable.');
        }
        return $json['account_info'] ?? $json;
    }

    /**
     * ----------- INTERNALS ------------
     */

    private function call(array $params): array
    {
        $params['api_key'] = $this->apiKey;
        if ($this->destination !== '') {
            $params['customer_id'] = $this->destination;
        }

        $url = self::ENDPOINT . '?' . http_build_query($params);
        $body = $this->httpGet($url);

        $json = json_decode($body, true);
        if (!is_array($json)) {
            throw new RuntimeException('Rainforest : réponse non JSON. Brut : ' . mb_substr($body, 0, 300));
        }
        if (!empty($json['request_info']['success']) && $json['request_info']['success'] === false) {
            throw new RuntimeException('Rainforest API error : ' . ($json['request_info']['message'] ?? 'unknown'));
        }
        if (!empty($json['error'])) {
            throw new RuntimeException('Rainforest API error : ' . (string)$json['error']);
        }

        return $json;
    }

    private function httpGet(string $url): string
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 90,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTPHEADER     => ['Accept: application/json'],
            CURLOPT_USERAGENT      => 'cafetiereagrain.fr / RainforestClient PHP',
        ]);
        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($body === false) {
            throw new RuntimeException('cURL : ' . $err);
        }
        if ($code !== 200) {
            throw new RuntimeException('HTTP ' . $code . ' : ' . mb_substr((string)$body, 0, 300));
        }
        return (string)$body;
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
