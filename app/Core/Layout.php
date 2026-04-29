<?php
// Layout helpers : escape, lien affiliation Amazon, build de CTA UTM.

declare(strict_types=1);

namespace App\Core;

final class Layout
{
    public static function loadConfig(): array
    {
        $configFile  = dirname(__DIR__, 2) . '/config/config.php';
        $exampleFile = dirname(__DIR__, 2) . '/config/config.example.php';
        if (is_file($configFile)) {
            return require $configFile;
        }
        if (is_file($exampleFile)) {
            return require $exampleFile;
        }
        return [];
    }

    public static function escape(string $s): string
    {
        return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Construit un lien d'affiliation Amazon avec tag + UTM.
     * Accepte un ASIN ou une URL Amazon complete.
     */
    public static function amazonLink(string $asinOrUrl, string $campaign = ''): string
    {
        $cfg = self::loadConfig();
        $tag = $cfg['affiliate']['amazon_tag'] ?? '';
        $base = $cfg['affiliate']['amazon_base'] ?? 'https://www.amazon.fr';

        // ASIN (10 caracteres alphanum) -> construit l'URL produit
        if (preg_match('/^[A-Z0-9]{10}$/', $asinOrUrl)) {
            $url = $base . '/dp/' . $asinOrUrl;
        } else {
            $url = $asinOrUrl;
        }

        // Ajoute le tag affiliate
        $sep = (strpos($url, '?') !== false) ? '&' : '?';
        if ($tag !== '' && strpos($url, 'tag=') === false) {
            $url .= $sep . 'tag=' . urlencode($tag);
            $sep = '&';
        }
        // UTM tracking interne
        if ($campaign !== '') {
            $url .= $sep . 'utm_source=cafetiereagrain'
                  . '&utm_medium=affiliate'
                  . '&utm_campaign=' . urlencode($campaign);
        }
        return $url;
    }

    /**
     * Construit une URL de tracking interne (/go/{slug}) qui redirige vers Amazon.
     */
    public static function trackingLink(string $destSlug): string
    {
        return '/go/' . urlencode($destSlug);
    }
}
