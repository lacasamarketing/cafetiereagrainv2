<?php
/**
 * Genere et publie un article via Claude API a partir du prochain topic en queue.
 *
 * Usage :
 *   - en cron OVH (1x/jour) :
 *       /usr/local/bin/php /home/cluster0XX/www/cafetiereagrain/scripts/generate_article.php >> /home/.../logs/cron.log 2>&1
 *   - en CLI manuel :
 *       php scripts/generate_article.php
 */

declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

use App\Core\ArticleGenerator;

$start = microtime(true);

try {
    $g = new ArticleGenerator();
    $id = $g->generateFromQueue();
    $elapsed = round(microtime(true) - $start, 1);
    fwrite(STDOUT, sprintf("[%s] OK article #%d publie en %ss\n", date('Y-m-d H:i:s'), $id, $elapsed));
    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, sprintf("[%s] ERR : %s\n", date('Y-m-d H:i:s'), $e->getMessage()));
    exit(1);
}
