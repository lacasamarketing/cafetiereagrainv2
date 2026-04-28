<?php
// Layout helpers : escape, lien affiliation Amazon, build de CTA UTM.
// Le site monétise via Amazon Partenaires France principalement.

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
     * Usage : Layout::amazonLink('B0CXXXXX', 'comparatif-uv-vs-co2')
     *         Layout::amazonLink('https://www.amazon.fr/dp/B0CXXXXX', 'mon-article')
     */
    public static function amazonLink(string $asinOrUrl, string $campaign = ''): string
    {
        $cfg = self::loadConfig();
        $tag = $cfg['affiliate']['amazon_tag'] ?? 