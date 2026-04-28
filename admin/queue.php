<?php
// Admin queue : liste, ajout, suppression de mots-cles

declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';
require __DIR__ . '/_layout.php';

use App\Core\Auth;
use App\Core\CSRF;
use App\Core\Queue;

Auth::requireLogin();

$error = null;
$success = null;

// Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!CSRF::verify($_POST['_csrf'] ?? null)) {
        $error = 'Session invalide.';
    } else {
        $action = $_POST['action'] ?? '';
        if ($action === 'add') {
            $kw = trim($_POST['keyword_target'] ?? '');
            if ($kw === '') {
                $error = 'Mot-cle requis.';
            } else {
                Queue::add([
                    'keyword_target' => $kw,
                    'title_hint'     => $_POST['title_hint'] ?? null,
                    'cluster'        => $_POST['cluster'] ?? 'general',
                    'persona'        => $_POST['persona'] ?? 'tous',
                    'priority'       => (int)($_POST['priority'] ?? 5),
                ]);
                $success = 'Mot-cle ajoute a la queue.';
            }
        } elseif ($action === 'delete') {
            Queue::delete((int)($_POST['id'] ?? 0));
            $success = 'Supprime.';
        } elseif ($action === 'reset') {
            Queue::reset((int)($_POST['id'] ?? 0));
            $success = 'Remis en pending.';
        }
    }
}

$queue = Queue::all();
$stats = Queue::stats();

admin_header('Queue articles');
?>

<div class="flex items-center justify-between mb-6">
    <h1 class="text-3xl font-black">Queue d'articles</h1>
    <div class="text-sm text-slate-500"><?= $stats['pending'] ?> à traiter · <?= $stats['done'] ?> faits · <?= $stats['failed'] ?> échoués</div>
</div>

<?php if ($success): ?>
    <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-3 mb-4 text-sm text-emerald-700"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="bg-red-50 border border-red-200 rounded-xl p-3 mb-4 text-sm text-red-700"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<!-- Stats cards -->
<div class="grid grid-cols-4 gap-4 mb-6">
    <div class="bg-amber-50 rounded-xl p-4 border border-amber-200">
        <div class="text-xs text-amber-700 font-semibold uppercase tracking-wide">À traiter</div>
        <div class="text-2xl font-black text-amber-900 mt-1"><?= $stats['pending'] ?></div>
    </div>
    <div class="bg-blue-50 rounded-xl p-4 border border-blue-200">
        <div class="text-xs text-blue-700 font-semibold uppercase tracking-wide">En cours</div>
        <div class="text-2xl font-black text-blue-900 mt-1"><?= $stats['in_progress'] ?></div>
    </div>
    <div class="bg-emerald-50 rounded-xl p-4 border border-emerald-200">
        <div class="text-xs text-emerald-700 font-semibold uppercase tracking-wide">Publiés</div>
        <div class="text-2xl font-black text-emerald-900 mt-1"><?= $stats['done'] ?></div>
    </div>
    <div class="bg-red-50 rounded-xl p-4 border border-red-200">
        <div class="text-xs text-red-700 font-semibold uppercase tracking-wide">Échoués</div>
        <div class="text-2xl font-black text-red-900 mt-1"><?= $stats['failed'] ?></div>
    </div>
</div>

<!-- Form ajout -->
<details class="bg-white rounded-2xl border border-slate-200 p-6 mb-6">
    <summary class="cursor-pointer font-bold text-lg">+ Ajouter un mot-clé</summary>
    <form method="POST" class="mt-4 space-y-4">
        <?= CSRF::field() ?>
        <input type="hidden" name="action" value="add">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-semibold mb-1">Mot-clé cible *</label>
                <input type="text" name="keyword_target" required class="w-full px-3 py-2 rounded-lg border border-slate-300">
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">Titre suggéré</label>
                <input type="text" name="title_hint" class="w-full px-3 py-2 rounded-lg border border-slate-300" placeholder="Auto si vide">
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">Cluster</label>
                <select name="cluster" class="w-full px-3 py-2 rounded-lg border border-slate-300">
                    <option value="use-case">Cas d'usage</option>
                    <option value="comparatif">Comparatif</option>
                    <option value="technique">Technique SEO</option>
                    <option value="tuto">Tutoriel</option>
                    <option value="general">Général</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">Persona</label>
                <select name="persona" class="w-full px-3 py-2 rounded-lg border border-slate-300">
                    <option value="tous">Tous</option>
                    <option value="ecommerce">E-commerçant</option>
                    <option value="affiliation">Affilié</option>
                    <option value="entrepreneur">Entrepreneur</option>
                    <option value="agence">Agence SEO</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">Priorité (1-10)</label>
                <input type="number" name="priority" min="1" max="10" value="5" class="w-full px-3 py-2 rounded-lg border border-slate-300">
            </div>
        </div>
        <button type="submit" class="bg-gradient-to-r from-indigo-600 to-pink-500 text-white font-semibold px-5 py-2.5 rounded-lg">Ajouter</button>
    </form>
</details>

<!-- Liste -->
<div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
    <table class="w-full">
        <thead class="bg-slate-50 border-b border-slate-200">
            <tr>
                <th class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500 px-4 py-3">Mot-clé</th>
                <th class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500 px-4 py-3">Cluster</th>
                <th class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500 px-4 py-3">Prio</th>
                <th class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500 px-4 py-3">Statut</th>
                <th class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500 px-4 py-3">Tentatives</th>
                <th class="px-4 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            <?php foreach ($queue as $q): ?>
                <tr class="hover:bg-slate-50/50">
                    <td class="px-4 py-3">
                        <div class="font-medium text-slate-900 text-sm"><?= htmlspecialchars($q['keyword_target'], ENT_QUOTES, 'UTF-8') ?></div>
                        <?php if ($q['title_hint']): ?>
                            <div class="text-xs text-slate-400 mt-0.5"><?= htmlspecialchars($q['title_hint'], ENT_QUOTES, 'UTF-8') ?></div>
                        <?php endif; ?>
                    </td>
                    <td class="px-4 py-3 text-xs text-slate-600"><?= htmlspecialchars($q['cluster'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="px-4 py-3 text-sm"><?= (int)$q['priority'] ?></td>
                    <td class="px-4 py-3">
                        <span class="inline-block px-2 py-0.5 rounded text-[10px] font-semibold uppercase <?php
                            echo match($q['status']) {
                                'pending'     => 'bg-amber-100 text-amber-700',
                                'in_progress' => 'bg-blue-100 text-blue-700',
                                'done'        => 'bg-emerald-100 text-emerald-700',
                                'failed'      => 'bg-red-100 text-red-700',
                                default       => 'bg-slate-100 text-slate-700'
                            };
                        ?>">
                            <?= htmlspecialchars($q['status'], ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    </td>
                    <td class="px-4 py-3 text-sm text-slate-500"><?= (int)$q['attempts'] ?></td>
                    <td class="px-4 py-3 text-right">
                        <?php if ($q['status'] === 'failed' || $q['status'] === 'in_progress'): ?>
                        <form method="POST" class="inline">
                            <?= CSRF::field() ?>
                            <input type="hidden" name="action" value="reset">
                            <input type="hidden" name="id" value="<?= (int)$q['id'] ?>">
                            <button type="submit" class="text-xs text-indigo-600 hover:underline">Reset</button>
                        </form>
                        <?php endif; ?>
                        <form method="POST" class="inline ml-2" onsubmit="return confirm('Supprimer ce mot-cle ?');">
                            <?= CSRF::field() ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= (int)$q['id'] ?>">
                            <button type="submit" class="text-xs text-red-600 hover:underline">🗑️</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($queue)): ?>
                <tr><td colspan="6" class="px-4 py-12 text-center text-slate-400">Queue vide. Importe la migration SQL puis ajoute des mots-clés.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php admin_footer(); ?>
