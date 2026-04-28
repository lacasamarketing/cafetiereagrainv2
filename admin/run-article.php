<?php
// Admin : déclencheur manuel de génération d'article via Claude API + ArticleGenerator
// Modèle calqué sur /admin/sync-now.php (sync Amazon)

declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';
require __DIR__ . '/_layout.php';

use App\Core\ArticleGenerator;
use App\Core\Cron;
use App\Core\CSRF;
use App\Core\Database;
use App\Core\Queue;

$result = null;
$lastRun = Cron::lastRun('article_daily');
$pdo = Database::pdo();

// Stats queue par cluster
$queueByCluster = [];
try {
    $rows = $pdo->query("SELECT cluster, COUNT(*) AS n FROM article_queue WHERE status = 'pending' GROUP BY cluster ORDER BY n DESC")->fetchAll();
    foreach ($rows as $r) {
        $queueByCluster[$r['cluster']] = (int)$r['n'];
    }
} catch (Throwable $e) {}
$totalPending = array_sum($queueByCluster);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!CSRF::verify($_POST['_csrf'] ?? null)) {
        $result = ['ok' => false, 'error' => 'Session invalide.'];
    } else {
        @set_time_limit(300);
        try {
            $g = new ArticleGenerator();

            // Si l'admin a forcé un cluster spécifique, on pioche sur ce cluster
            $forceCluster = $_POST['cluster'] ?? '';
            if ($forceCluster !== '' && in_array($forceCluster, ['comparatif','guide','test','saison','usage','diy','science','sante','faq'], true)) {
                $stmt = $pdo->prepare("SELECT * FROM article_queue WHERE status = 'pending' AND cluster = ? ORDER BY priority DESC, created_at ASC LIMIT 1");
                $stmt->execute([$forceCluster]);
                $topic = $stmt->fetch();
                if (!$topic) {
                    throw new RuntimeException('Aucun topic en attente sur le cluster ' . $forceCluster);
                }
                // Marque in_progress et incrément attempts
                $pdo->prepare("UPDATE article_queue SET status = 'in_progress', attempts = attempts + 1, last_attempt_at = NOW() WHERE id = ?")
                    ->execute([(int)$topic['id']]);
                $articleId = $g->generateFromTopic($topic);
            } else {
                $articleId = $g->generateFromQueue();
            }

            // Récupère le détail pour affichage
            $stmt = $pdo->prepare("SELECT slug, title, cluster, reading_time FROM articles WHERE id = ?");
            $stmt->execute([$articleId]);
            $article = $stmt->fetch();

            // Marque le cron comme exécuté
            $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('cron:article_daily', ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)")
                ->execute([(string)time()]);
            $lastRun = time();

            $result = ['ok' => true, 'article_id' => $articleId, 'article' => $article];
        } catch (Throwable $e) {
            $result = ['ok' => false, 'error' => $e->getMessage()];
        }
    }
}

admin_header('Auto-pub article');
?>

<div class="mb-6">
    <h1 class="display" style="font-size: 2.25rem; font-weight: 500; letter-spacing: -0.02em;">Auto-pub article</h1>
    <p style="color: var(--pam-muted); margin-top: 0.25rem;">Génère et publie un article via Claude API en piochant un sujet de la queue. Routine quotidienne déclenchée automatiquement par les visites publiques (toutes les 24h).</p>
</div>

<div class="pam-card p-6 mb-6">
    <div class="text-sm" style="color: var(--pam-muted);">
        <strong style="color: var(--pam-ink);">Dernier article auto-publié :</strong>
        <?php if ($lastRun): ?>
            <?= date('d/m/Y H:i:s', $lastRun) ?> (il y a <?= round((time() - $lastRun) / 3600, 1) ?> h)
        <?php else: ?>
            jamais — la routine se déclenchera à la prochaine visite publique
        <?php endif; ?>
    </div>
    <div class="text-sm mt-3" style="color: var(--pam-muted);">
        <strong style="color: var(--pam-ink);">Queue topics en attente (<?= $totalPending ?>) :</strong>
        <?php if ($queueByCluster): ?>
            <span class="text-xs">
            <?php foreach ($queueByCluster as $c => $n): ?>
                <span class="ml-2 px-2 py-0.5 rounded inline-block" style="background:rgba(74,44,20,0.10);color:var(--forest);"><?= htmlspecialchars($c, ENT_QUOTES, 'UTF-8') ?> : <?= $n ?></span>
            <?php endforeach; ?>
            </span>
        <?php else: ?>
            queue vide → ajoute via /admin/queue.php ou ré-importe extend_topics_editorial.sql
        <?php endif; ?>
    </div>
</div>

<form method="POST" class="pam-card p-6 mb-6">
    <?= CSRF::field() ?>

    <h2 class="font-bold text-lg mb-4 display" style="font-weight: 500;">Forcer la publication maintenant</h2>

    <div class="space-y-4">
        <div>
            <label class="block text-xs font-semibold uppercase tracking-wide mb-1" style="color: var(--pam-muted);">Cluster à privilégier (optionnel)</label>
            <select name="cluster" class="w-full px-3 py-2 rounded-lg border bg-white" style="border-color: var(--pam-line);">
                <option value="">— Auto (priorité décroissante, tous clusters confondus)</option>
                <?php foreach ($queueByCluster as $c => $n): ?>
                    <option value="<?= htmlspecialchars($c, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($c, ENT_QUOTES, 'UTF-8') ?> (<?= $n ?> en attente)</option>
                <?php endforeach; ?>
            </select>
            <p class="text-xs mt-1" style="color: var(--pam-muted);">Stratégie 7-jours : lundi=test / mardi=comparatif / mercredi=guide / jeudi=diy / vendredi=saison / samedi=science / dimanche=faq.</p>
        </div>
    </div>

    <button type="submit" class="pam-btn-primary mt-6" style="padding: 0.7rem 1.5rem; font-size: 0.95rem;">Lancer la génération</button>
    <p class="text-xs mt-3" style="color: var(--pam-muted);">⏱️ Compte 30 à 90 secondes. Claude rédige ~1500 mots et publie via insert direct en BDD.</p>
</form>

<?php if ($result !== null): ?>
    <div class="pam-card p-6">
        <h2 class="font-bold text-lg mb-3 display" style="font-weight: 500;">Résultat</h2>
        <?php if ($result['ok']): ?>
            <div class="text-sm mb-3" style="color: var(--forest);">✓ Article publié avec succès</div>
            <?php if (!empty($result['article'])): ?>
                <div class="space-y-2 text-sm">
                    <div><strong>Titre :</strong> <?= htmlspecialchars((string)$result['article']['title'], ENT_QUOTES, 'UTF-8') ?></div>
                    <div><strong>Cluster :</strong> <?= htmlspecialchars((string)$result['article']['cluster'], ENT_QUOTES, 'UTF-8') ?></div>
                    <div><strong>Temps de lecture :</strong> <?= (int)$result['article']['reading_time'] ?> min</div>
                    <div><strong>URL :</strong> <a href="/blog/<?= htmlspecialchars((string)$result['article']['slug'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" style="color: var(--forest);">Voir l'article →</a></div>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="text-sm" style="color: #dc2626;">✗ Erreur : <?= htmlspecialchars($result['error'] ?? 'inconnue', ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php admin_footer(); ?>
