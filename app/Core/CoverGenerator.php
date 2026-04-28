<?php
// Generateur de cover SVG pour les articles (style Tech/Dark)

declare(strict_types=1);

namespace App\Core;

final class CoverGenerator
{
    /**
     * Genere un SVG de cover pour un article.
     * Style : Tech / Dark (navy + grid + gradient accent).
     *
     * @param string $title         Titre de l'article
     * @param string $cluster       Cluster (use-case / comparatif / technique / tuto / general)
     * @param int    $readingTime   Temps de lecture en minutes
     */
    public static function generate(string $title, string $cluster = 'general', int $readingTime = 5): string
    {
        $lines = self::wrapTitle($title, 22, 3);
        $accentLine = array_pop($lines); // La derniere ligne est en gradient

        $clusterLabel = self::clusterPath($cluster);
        $readingLabel = $readingTime . ' min de lecture';

        $y = self::startY(count($lines) + 1);

        $titleSvg = '';
        foreach ($lines as $i => $line) {
            $titleSvg .= '<text x="60" y="' . ($y + $i * 84) . '" font-family="Arial, sans-serif" font-weight="900" font-size="72" fill="white">' . htmlspecialchars($line, ENT_QUOTES | ENT_XML1, 'UTF-8') . '</text>';
        }
        $titleSvg .= '<text x="60" y="' . ($y + count($lines) * 84) . '" font-family="Arial, sans-serif" font-weight="900" font-size="72" fill="url(#gradAccent)">' . htmlspecialchars($accentLine, ENT_QUOTES | ENT_XML1, 'UTF-8') . '</text>';

        return <<<SVG
<?xml version="1.0" encoding="UTF-8"?>
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 630" width="1200" height="630">
    <defs>
        <linearGradient id="gradAccent" x1="0" y1="0" x2="1" y2="0">
            <stop offset="0%" stop-color="#6366f1"/>
            <stop offset="50%" stop-color="#8b5cf6"/>
            <stop offset="100%" stop-color="#ec4899"/>
        </linearGradient>
        <pattern id="grid" width="40" height="40" patternUnits="userSpaceOnUse">
            <path d="M 40 0 L 0 0 0 40" fill="none" stroke="white" stroke-width="0.5" opacity="0.08"/>
        </pattern>
        <radialGradient id="halo1" cx="0.5" cy="0.5" r="0.5">
            <stop offset="0%" stop-color="#6366f1" stop-opacity="0.35"/>
            <stop offset="100%" stop-color="#6366f1" stop-opacity="0"/>
        </radialGradient>
        <radialGradient id="halo2" cx="0.5" cy="0.5" r="0.5">
            <stop offset="0%" stop-color="#ec4899" stop-opacity="0.25"/>
            <stop offset="100%" stop-color="#ec4899" stop-opacity="0"/>
        </radialGradient>
    </defs>

    <!-- Fond -->
    <rect width="1200" height="630" fill="#0f172a"/>
    <rect width="1200" height="630" fill="url(#grid)"/>

    <!-- Halos colorés -->
    <circle cx="1000" cy="150" r="240" fill="url(#halo1)"/>
    <circle cx="1080" cy="500" r="170" fill="url(#halo2)"/>

    <!-- Logo brand (coin haut-gauche) -->
    <rect x="60" y="60" width="54" height="54" rx="12" fill="url(#gradAccent)"/>
    <text x="87" y="100" text-anchor="middle" font-family="Arial, sans-serif" font-weight="900" font-size="34" fill="white">r</text>

    <!-- Chemin / cluster -->
    <text x="60" y="155" font-family="'Courier New', monospace" font-weight="700" font-size="22" fill="#818cf8">&gt; {$clusterLabel}</text>

    <!-- Titre -->
    {$titleSvg}

    <!-- Footer -->
    <text x="60" y="585" font-family="'Courier New', monospace" font-weight="700" font-size="18" fill="#94a3b8">redactionavecia.fr</text>
    <text x="1140" y="585" text-anchor="end" font-family="'Courier New', monospace" font-weight="700" font-size="18" fill="#64748b">{$readingLabel}</text>

    <!-- Ligne finale -->
    <line x1="60" y1="560" x2="1140" y2="560" stroke="#334155" stroke-width="1"/>
</svg>
SVG;
    }

    /**
     * Decoupe un titre en lignes en essayant d'equilibrer les longueurs
     */
    private static function wrapTitle(string $title, int $maxCharsPerLine = 22, int $maxLines = 3): array
    {
        $title = trim($title);
        $words = preg_split('/\s+/', $title) ?: [];
        if (empty($words)) {
            return [$title];
        }

        $lines = [];
        $current = '';
        foreach ($words as $w) {
            if ($current === '') {
                $current = $w;
            } elseif (mb_strlen($current . ' ' . $w) <= $maxCharsPerLine) {
                $current .= ' ' . $w;
            } else {
                $lines[] = $current;
                $current = $w;
                if (count($lines) >= $maxLines - 1) {
                    break;
                }
            }
        }
        if ($current !== '') {
            $lines[] = $current;
        }

        // Si trop long, couper brutalement
        while (count($lines) > $maxLines) {
            $last = array_pop($lines);
            $lines[count($lines) - 1] = rtrim((string)end($lines)) . ' ' . $last;
        }

        return $lines;
    }

    private static function clusterPath(string $cluster): string
    {
        $map = [
            'use-case'   => '/blog/cas-usage',
            'comparatif' => '/blog/comparatif',
            'technique'  => '/blog/technique',
            'tuto'       => '/blog/tuto',
            'general'    => '/blog',
        ];
        return $map[$cluster] ?? '/blog';
    }

    private static function startY(int $totalLines): int
    {
        // Centre vertical approximatif pour 2-3 lignes
        return match ($totalLines) {
            2 => 330,
            3 => 280,
            4 => 230,
            default => 320,
        };
    }
}
