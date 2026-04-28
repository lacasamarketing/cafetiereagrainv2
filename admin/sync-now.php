<?php
// Admin : déclencheur manuel du sync Amazon via Rainforest

declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';
require __DIR__ . '/_layout.php';

use App\Core\Cron;
use App\Core\AmazonSync;
use App\Core\CSRF;
use App\Core\Database;

$result = null;
$lastRun = Cron::lastRun('amazon_sync');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!CSRF::verify($_POST['_csrf'] ?? null)) {
        $result = ['ok' => false, 'error' => 'Session invalide.'];
    } else {
        $opts = [
            'discover' => !empty($_POST['discover']),
            'only_slug' => !empty($_POST['slug']) ? trim((string)$_POST['slug']) : null,
            'dry_run' => !empty($_POST['dry_run']),
        ];
        @set_time_limit(300);
        try {
            $sync = new AmazonSync();
            $stats = $sync->run($opts);
            // Marque comme exécuté (même en dry_run)
            $pdo = Database::pdo();
            $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('cron:amazon_sync', ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)")
                ->execute([(string)time()]);
            $result = ['ok' => true, 'stats' => $stats, 'opts' => $opts];
            $lastRun = time();
        } catch (\Throwable $e) {
            $result = ['ok' => false, 'error' => $e->getMessage()];
        }
    }
}

admin_header('Sync Amazon');
?>

<div class="mb-6">
    <h1 class="display" style="font-size: 2.25rem; font-weight: 500; letter-spacing: -0.02em;">Sync Amazon</h1>
    <p style="color: var(--pam-muted); margin-top: 0.25rem;">Déclencheur manuel de la routine de synchronisation produits via Rainforest API.</p>
</div>

<div class="pam-card p-6 mb-6">
    <div class="text-sm" style="color: var(--pam-muted);">
        <strong style="color: var(--pam-ink);">Dernier sync :</strong>
        <?php if ($lastRun): ?>
            <?= date('d/m/Y H:i:s', $lastRun) ?> (il y a <?= round((time() - $lastRun) / 86400, 1) ?> jours)
        <?php else: ?>
            jamais — la routine se déclenchera à la prochaine visite publique
        <?php endif; ?>
    </div>
    <div class="text-sm mt-3" style="color: var(--pam-muted);">
        <strong style="color: var(--pam-ink);">Comment ça marche :</strong>
        La routine tourne automatiquement en arrière-plan toutes les 7 jours, déclenchée par la 1<sup>re</sup> visite publique après expiration. Ce panneau te permet de la forcer maintenant.
    </div>
</div>

<form method="POST" class="pam-card p-6 mb-6">
    <?= CSRF::field() ?>

    <h2 class="font-bold text-lg mb-4 display" style="font-weight: 500;">Forcer un sync maintenant</h2>

    <div class="space-y-4">
        <label class="flex items-start gap-3">
            <input type="checkbox" name="dry_run" value="1" class="mt-1">
            <div>
                <div class="font-medium">Mode dry-run (aucune écriture)</div>
                <div class="text-xs" style="color: var(--pam-muted);">Simule la sync sans rien écrire en BDD. Utile pour tester sans consommer de crédits.</div>
            </div>
        </label>

        <label class="flex items-start gap-3">
            <input type="checkbox" name="discover" value="1" class="mt-1">
            <div>
                <div class="font-medium">Découverte de nouveaux produits</div>
                <div class="text-xs" style="color: var(--pam-muted);">Explore les keyword_target en queue pour ajouter de nouveaux produits en draft. Consomme +5 crédits Rainforest.</div>
            </div>
        </label>

        <div>
            <label class="block text-xs font-semibold uppercase tracking-wide mb-1" style="color: var(--pam-muted);">Limiter à un seul slug (optionnel)</label>
            <input type="text" name="slug" placeholder="mosquito-magnet-patriot" class="w-full px-3 py-2 rounded-lg border" style="border-color: var(--pam-line);">
            <p class="text-xs mt-1" style="color: var(--pam-muted);">Utile pour debug. Laisse vide pour sync tous les produits.</p>
        </div>
    </div>

    <button type="submit" class="pam-btn-primary mt-6" style="padding: 0.7rem 1.5rem; font-size: 0.95rem;">Lancer la sync</button>
</form>

<?php if ($result !== null): ?>
    <div class="pam-card p-6">
        <h2 class="font-bold text-lg mb-3 display" style="font-weight: 500;">Résultat</h2>
        <?php if ($result['ok']): ?>
            <div class="text-sm mb-3" style="color: var(--forest);">✓ Sync terminée avec succès</div>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-4 text-sm">
                <div><strong>Placeholders remplis :</strong> <?= (int)$result['stats']['placeholder_filled'] ?></div>
                <div><strong>Prix refreshés :</strong> <?= (int)$result['stats']['price_refreshed'] ?></div>
                <div><strong>Nouveaux produits :</strong> <?= (int)$result['stats']['discovered'] ?></div>
                <div><strong>Erreurs :</strong> <?= (int)$result['stats']['errors'] ?></div>
                <div><strong>Crédits estimés :</strong> ~<?= (int)$result['stats']['credits_estimated'] ?></div>
                <?php if (!empty($result['opts']['dry_run'])): ?>
                    <div style="color: #c97a1e;"><strong>⚠ DRY RUN</strong> — aucune écriture en BDD</div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="text-sm" style="color: #dc2626;">✗ Erreur : <?= htmlspecialchars($result['error'] ?? 'inconnue', ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php admin_footer(); ?>
