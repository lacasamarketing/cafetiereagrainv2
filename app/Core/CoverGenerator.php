<?php
// Generateur de cover SVG pour les articles - theme café crème/espresso
declare(strict_types=1);

namespace App\Core;

final class CoverGenerator
{
    /**
     * Genere un SVG cover pour un article (theme café : crème + espresso + grain)
     */
    public static function generate(string $title, string $cluster = 'general', int $readingTime = 5): string
    {
        $lines = self::wrapTitle($title, 24, 3);
        $accentLine = array_pop($lines);
        $clusterLabel = strtoupper(str_replace('-', ' ', $cluster));
        $readingLabel = $readingTime . ' MIN DE LECTURE';

        // Hash pour varier les couleurs/positions selon le slug
        $hash = crc32($title);
        $hue = ($hash % 30) - 15; // -15 à +15 degré sur la teinte
        $beanRotateA = ($hash % 60) - 30;
        $beanRotateB = (($hash >> 4) % 60) - 30;

        $y = self::startY(count($lines) + 1);

        $titleSvg = '';
        foreach ($lines as $i => $line) {
            $titleSvg .= '<text x="60" y="' . ($y + $i * 78) . '" font-family="Georgia, serif" font-weight="700" font-size="62" fill="#2a1810" letter-spacing="-1">' . htmlspecialchars($line, ENT_QUOTES | ENT_XML1, 'UTF-8') . '</text>';
        }
        $titleSvg .= '<text x="60" y="' . ($y + count($lines) * 78) . '" font-family="Georgia, serif" font-style="italic" font-weight="700" font-size="62" fill="url(#accentGrad)" letter-spacing="-1">' . htmlspecialchars($accentLine, ENT_QUOTES | ENT_XML1, 'UTF-8') . '</text>';

        $titleEsc = htmlspecialchars(self::wrapTitle($title, 80, 1)[0] ?? '', ENT_QUOTES | ENT_XML1, 'UTF-8');
        $clusterEsc = htmlspecialchars($clusterLabel, ENT_QUOTES | ENT_XML1, 'UTF-8');
        $readingEsc = htmlspecialchars($readingLabel, ENT_QUOTES | ENT_XML1, 'UTF-8');

        return <<<SVG
<?xml version="1.0" encoding="UTF-8"?>
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 630" width="1200" height="630" role="img" aria-label="{$titleEsc}">
    <defs>
        <linearGradient id="bgGrad" x1="0" y1="0" x2="1" y2="1">
            <stop offset="0%" stop-color="#faf6ef"/>
            <stop offset="100%" stop-color="#f3ece0"/>
        </linearGradient>
        <linearGradient id="accentGrad" x1="0" y1="0" x2="1" y2="0">
            <stop offset="0%" stop-color="#a87149"/>
            <stop offset="50%" stop-color="#c89968"/>
            <stop offset="100%" stop-color="#8b5e3c"/>
        </linearGradient>
        <radialGradient id="halo" cx="0.7" cy="0.3" r="0.6">
            <stop offset="0%" stop-color="#c89968" stop-opacity="0.25"/>
            <stop offset="100%" stop-color="#c89968" stop-opacity="0"/>
        </radialGradient>
    </defs>
    <rect width="1200" height="630" fill="url(#bgGrad)"/>
    <rect width="1200" height="630" fill="url(#halo)"/>

    <!-- Grains de café décoratifs (3 grains stylisés) -->
    <g opacity="0.85" transform="translate(960 140) rotate({$beanRotateA})">
        <ellipse cx="0" cy="0" rx="48" ry="60" fill="#3d2418" stroke="#1a0d07" stroke-width="2.5"/>
        <path d="M 0 -50 Q -12 -10 0 0 Q 12 10 0 50" fill="none" stroke="#c89968" stroke-width="3.5" stroke-linecap="round"/>
    </g>
    <g opacity="0.7" transform="translate(1060 280) rotate({$beanRotateB})">
        <ellipse cx="0" cy="0" rx="36" ry="46" fill="#5a3724" stroke="#1a0d07" stroke-width="2"/>
        <path d="M 0 -40 Q -9 -8 0 0 Q 9 8 0 40" fill="none" stroke="#c89968" stroke-width="2.8" stroke-linecap="round"/>
    </g>
    <g opacity="0.55" transform="translate(900 460) rotate(15)">
        <ellipse cx="0" cy="0" rx="28" ry="36" fill="#3d2418" stroke="#1a0d07" stroke-width="1.6"/>
        <path d="M 0 -30 Q -7 -6 0 0 Q 7 6 0 30" fill="none" stroke="#c89968" stroke-width="2.2" stroke-linecap="round"/>
    </g>

    <!-- Tag cluster en haut -->
    <rect x="60" y="55" rx="22" ry="22" width="auto" height="44" fill="#2a1810"/>
    <text x="80" y="84" font-family="Arial, sans-serif" font-weight="700" font-size="16" fill="#faf6ef" letter-spacing="2">{$clusterEsc}</text>

    <!-- Titre principal -->
    {$titleSvg}

    <!-- Bottom : marque + reading time -->
    <line x1="60" y1="540" x2="200" y2="540" stroke="#c89968" stroke-width="3"/>
    <text x="60" y="580" font-family="Georgia, serif" font-weight="600" font-size="22" fill="#2a1810">cafetiereagrain.fr</text>
    <text x="60" y="608" font-family="Arial, sans-serif" font-size="14" fill="#6f5640" letter-spacing="2">{$readingEsc}</text>
</svg>
SVG;
    }

    /** Decoupe le titre en N lignes max */
    private static function wrapTitle(string $title, int $maxCharsPerLine, int $maxLines): array
    {
        $words = explode(' ', $title);
        $lines = [];
        $current = '';
        foreach ($words as $word) {
            if (mb_strlen($current . ' ' . $word) > $maxCharsPerLine) {
                if ($current !== '') $lines[] = $current;
                $current = $word;
            } else {
                $current = $current === '' ? $word : $current . ' ' . $word;
            }
            if (count($lines) >= $maxLines) break;
        }
        if ($current !== '' && count($lines) < $maxLines) $lines[] = $current;
        return array_slice($lines, 0, $maxLines);
    }

    private static function startY(int $totalLines): int
    {
        // Centre vertical du bloc titre
        $totalHeight = $totalLines * 78;
        $availableSpace = 540 - 130; // entre le tag (haut ~100) et la ligne footer (~540)
        $start = 130 + (int)(($availableSpace - $totalHeight) / 2) + 60;
        return max(220, $start);
    }
}
