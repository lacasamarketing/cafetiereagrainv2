#!/usr/bin/env php
<?php
/**
 * discover_keywords.php — découverte mensuelle de mots-clés via Mangools KWFinder.
 *
 * Logique :
 *  1. 8 seeds café (volume bonus, marques, longue traîne)
 *  2. Pour chaque seed : searchByKeyword (vol/KD/CPC) + relatedKeywords filtré
 *  3. Filtres opportunité : volume ≥ 150, KD ≤ 30
 *  4. Push en article_queue avec priority calculée (INSERT IGNORE = anti-doublon natif)
 *  5. Auto-détection des marques mentionnées → cluster pré-rempli
 *
 * Cron OVH (mensuel, 1er du mois 7h) :
 *   0 7 1 * * /usr/local/php8.2/bin/php /home/.../scripts/discover_keywords.php >> logs/discover.log 2>&1
 *
 * Budget : 8 seeds × 2 endpoints (search + related) = 16 requests max/mois
 *           Cache 90j élimine les ré-appels → conso réelle ≤ 8 requests/mois après le 1er run.
 */

declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

use App\Core\Database;
use App\Core\MangoolsClient;
use App\Core\Queue;

$start = microtime(true);
echo sprintf("[%s] discover_keywords.php starting\n", date('Y-m-d H:i:s'));

// 8 seeds café — couvrent marques principales + longue traîne générique
$seeds = [
    'cafetière à grain',
    'machine à café broyeur',
    'delonghi magnifica',
    'philips lattego',
    'krups evidence',
    'jura cafetière',
    'sage barista',
    'meilleure cafetière à grain',
];

// Filtres opportunité (paramétrables)
$maxKd = 30;
$minVolume = 150;
$maxAddedPerSeed = 5; // évite le spam de la queue

$stats = [
    'seeds_scanned'   => 0,
    'kw_found'        => 0,
    'kw_added'        => 0,
    'kw_skipped'      => 0,
    'api_calls'       => 0,
    'errors'          => 0,
];

try {
    $client = new MangoolsClient();
    echo sprintf("→ Budget Mangools restant ce mois : %d/%d\n", $client->getRemainingBudget(), 10);

    foreach ($seeds as $seed) {
        $stats['seeds_scanned']++;
        echo sprintf("\n· seed: \"%s\"\n", $seed);

        try {
            // 1) Search direct
            $main = $client->searchByKeyword($seed);
            if ($main) {
                $stats['api_calls']++;
                $stats['kw_found']++;
                if ($main['volume'] >= $minVolume && $main['kd'] <= $maxKd) {
                    $added = addToQueue($seed, $main, detectCluster($seed), detectPersona($seed));
                    if ($added) {
                        $stats['kw_added']++;
                        echo sprintf("  + \"%s\" vol=%d KD=%d → priorité %d\n", $seed, $main['volume'], $main['kd'], $added);
                    } else {
                        $stats['kw_skipped']++;
                        echo sprintf("  ~ \"%s\" déjà en queue (skip)\n", $seed);
                    }
                } else {
                    echo sprintf("  - \"%s\" hors filtres (vol=%d KD=%d)\n", $seed, $main['volume'], $main['kd']);
                }
            }

            // 2) Related (déjà filtré côté client)
            $related = $client->relatedKeywords($seed, $maxKd, $minVolume);
            $stats['api_calls']++;
            $count = 0;
            foreach (array_slice($related, 0, $maxAddedPerSeed) as $kw) {
                $stats['kw_found']++;
                $added = addToQueue($kw['keyword'], $kw, detectCluster($kw['keyword']), detectPersona($kw['keyword']));
                if ($added) {
                    $stats['kw_added']++;
                    $count++;
                    echo sprintf("  + (related) \"%s\" vol=%d KD=%d → priorité %d\n", $kw['keyword'], $kw['volume'], $kw['kd'], $added);
                } else {
                    $stats['kw_skipped']++;
                }
            }
            echo sprintf("  → %d nouveau(x) related ajouté(s)\n", $count);

        } catch (\Throwable $e) {
            $stats['errors']++;
            echo sprintf("  × erreur seed \"%s\" : %s\n", $seed, $e->getMessage());
        }
    }

    // Log dans cron_log
    Database::pdo()->prepare(
        "INSERT INTO cron_log (job_name, status, message, duration_ms) VALUES (?, ?, ?, ?)"
    )->execute([
        'discover_keywords',
        $stats['errors'] > 0 ? 'error' : 'success',
        json_encode($stats),
        (int)((microtime(true) - $start) * 1000),
    ]);

    echo "\n=== DISCOVER COMPLETED ===\n";
    foreach ($stats as $k => $v) echo sprintf("  %-18s : %d\n", $k, $v);
    echo sprintf("  Durée : %ss\n", round(microtime(true) - $start, 1));
    exit($stats['errors'] > 0 ? 2 : 0);

} catch (\Throwable $e) {
    fwrite(STDERR, sprintf("[%s] FATAL : %s\n", date('Y-m-d H:i:s'), $e->getMessage()));
    exit(1);
}

// ============= HELPERS =============

/**
 * Pousse un keyword en queue. Retourne la priority calculée si ajouté, 0 si déjà présent.
 */
function addToQueue(string $keyword, array $kwData, string $cluster, string $persona): int
{
    $vol = (int)($kwData['volume'] ?? 0);
    $kd  = (int)($kwData['kd'] ?? 30);

    // Score priority : volume normalisé + bonus KD bas + bonus mentionne marque
    $priority = (int)round(min(10,
        ($vol / 200)
        + ((30 - $kd) * 0.2)
        + (preg_match('/(delonghi|philips|krups|saeco|jura|sage|melitta|nivona|miele|gaggia)/i', $keyword) ? 2 : 0)
    ));
    $priority = max(3, min(10, $priority));

    $kwNorm = MangoolsClient::normalizeKey($keyword);

    $pdo = Database::pdo();
    $stmt = $pdo->prepare(
        "INSERT IGNORE INTO article_queue
            (keyword_target, keyword_norm, cluster, persona, source, volume_monthly, kd_score, priority, status)
         VALUES (?, ?, ?, ?, 'mangools', ?, ?, ?, 'pending')"
    );
    $stmt->execute([
        mb_substr($keyword, 0, 150),
        $kwNorm,
        $cluster,
        $persona,
        $vol,
        $kd,
        $priority,
    ]);
    return $stmt->rowCount() > 0 ? $priority : 0;
}

/**
 * Détecte le cluster éditorial à partir du keyword.
 */
function detectCluster(string $kw): string
{
    $kw = mb_strtolower($kw);
    if (preg_match('/\b(vs|versus|comparatif|comparer|comparé)\b/', $kw)) return 'comparatif';
    if (preg_match('/\b(test|avis|review)\b/', $kw)) return 'test';
    if (preg_match('/\b(comment|guide|choisir|meilleur|conseil|critère|durée)\b/', $kw)) return 'guide';
    return 'general';
}

/**
 * Détecte le persona ciblé par le keyword.
 */
function detectPersona(string $kw): string
{
    $kw = mb_strtolower($kw);
    if (preg_match('/\b(bureau|entreprise|professionnel|restaurant|hôtel|pro)\b/', $kw)) return 'pro';
    if (preg_match('/\b(barista|expert|connaisseur)\b/', $kw)) return 'restaurateur';
    if (preg_match('/\b(maison|chez soi|particulier|cuisine|famille)\b/', $kw)) return 'particulier';
    return 'tous';
}
