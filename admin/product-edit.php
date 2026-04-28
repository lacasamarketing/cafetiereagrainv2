<?php
// Admin product edit / create

declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';
require __DIR__ . '/_layout.php';

use App\Core\Database;
use App\Core\CSRF;

$pdo = Database::pdo();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isNew = $id === 0;
$msg = null;
$msgType = 'ok';

// Save handler
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!CSRF::verify($_POST['_csrf'] ?? null)) {
        $msg = 'Session invalide. Recharge la page.';
        $msgType = 'err';
    } else {
        $data = [
            'slug'         => trim((string)($_POST['slug'] ?? '')),
            'name'         => trim((string)($_POST['name'] ?? '')),
            'brand'        => trim((string)($_POST['brand'] ?? '')) ?: null,
            'asin'         => strtoupper(preg_replace('/[^A-Z0-9]/i', '', (string)($_POST['asin'] ?? ''))) ?: null,
            'amazon_url'   => trim((string)($_POST['amazon_url'] ?? '')) ?: null,
            'technology'   => $_POST['technology'] ?? 'combine',
            'surface_m2'   => $_POST['surface_m2'] !== '' ? (int)$_POST['surface_m2'] : null,
            'noise_db'     => $_POST['noise_db'] !== '' ? (float)str_replace(',', '.', $_POST['noise_db']) : null,
            'autonomy_hours' => $_POST['autonomy_hours'] !== '' ? (int)$_POST['autonomy_hours'] : null,
            'price_eur'    => $_POST['price_eur'] !== '' ? (float)str_replace(',', '.', $_POST['price_eur']) : null,
            'rating'       => $_POST['rating'] !== '' ? (float)str_replace(',', '.', $_POST['rating']) : null,
            'rank_global'  => $_POST['rank_global'] !== '' ? (int)$_POST['rank_global'] : null,
            'badge'        => trim((string)($_POST['badge'] ?? '')) ?: null,
            'target_use'   => trim((string)($_POST['target_use'] ?? '')) ?: null,
            'verdict'      => trim((string)($_POST['verdict'] ?? '')) ?: null,
            'pitch'        => trim((string)($_POST['pitch'] ?? '')) ?: null,
            'image_url'    => trim((string)($_POST['image_url'] ?? '')) ?: null,
            'status'       => in_array($_POST['status'] ?? '', ['draft', 'published', 'archived'], true) ? $_POST['status'] : 'draft',
        ];

        $pros = array_values(array_filter(array_map('trim', explode("\n", (string)($_POST['pros'] ?? '')))));
        $cons = array_values(array_filter(array_map('trim', explode("\n", (string)($_POST['cons'] ?? '')))));
        $data['pros'] = !empty($pros) ? json_encode($pros, JSON_UNESCAPED_UNICODE) : null;
        $data['cons'] = !empty($cons) ? json_encode($cons, JSON_UNESCAPED_UNICODE) : null;

        try {
            if ($isNew) {
                if ($data['slug'] === '' || $data['name'] === '') {
                    throw new RuntimeException('Slug et nom obligatoires.');
                }
                $cols = implode(', ', array_keys($data));
                $place = implode(', ', array_fill(0, count($data), '?'));
                $stmt = $pdo->prepare("INSERT INTO products ($cols) VALUES ($place)");
                $stmt->execute(array_values($data));
                $id = (int)$pdo->lastInsertId();
                header('Location: /admin/product-edit.php?id=' . $id . '&saved=1');
                exit;
            } else {
                $set = implode(' = ?, ', array_keys($data)) . ' = ?';
                $stmt = $pdo->prepare("UPDATE products SET $set WHERE id = ?");
                $stmt->execute([...array_values($data), $id]);
                $msg = 'Produit enregistré avec succès.';
            }
        } catch (Throwable $e) {
            $msg = 'Erreur : ' . $e->getMessage();
            $msgType = 'err';
        }
    }
}

if (!empty($_GET['saved'])) {
    $msg = 'Produit créé avec succès.';
}

// Load product if edit
$p = [
    'slug' => '', 'name' => '', 'brand' => '', 'asin' => '', 'amazon_url' => '',
    'technology' => 'combine', 'surface_m2' => '', 'noise_db' => '', 'autonomy_hours' => '',
    'price_eur' => '', 'rating' => '', 'rank_global' => '', 'badge' => '', 'target_use' => '',
    'verdict' => '', 'pitch' => '', 'pros' => null, 'cons' => null, 'image_url' => '', 'status' => 'draft',
];
if (!$isNew) {
    $stmt = $pdo->prepare('SELECT * FROM products WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $loaded = $stmt->fetch();
    if (!$loaded) {
        header('Location: /admin/products.php');
        exit;
    }
    $p = array_merge($p, $loaded);
}

$prosTxt = '';
$consTxt = '';
if (!empty($p['pros'])) { $arr = json_decode((string)$p['pros'], true); if (is_array($arr)) $prosTxt = implode("\n", $arr); }
if (!empty($p['cons'])) { $arr = json_decode((string)$p['cons'], true); if (is_array($arr)) $consTxt = implode("\n", $arr); }

admin_header($isNew ? 'Nouveau produit' : 'Éditer : ' . ($p['name'] ?? ''));
?>

<div class="mb-6 flex items-center justify-between flex-wrap gap-3">
    <div>
        <a href="/admin/products.php" class="text-sm" style="color: var(--pam-muted);">← Tous les produits</a>
        <h1 class="display mt-2" style="font-size: 2rem; font-weight: 500; letter-spacing: -0.02em;">
            <?= $isNew ? 'Nouveau produit' : 'Éditer : ' . htmlspecialchars((string)$p['name'], ENT_QUOTES, 'UTF-8') ?>
        </h1>
    </div>
    <?php if (!$isNew): ?>
        <a href="/tests/<?= htmlspecialchars((string)$p['slug'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" class="text-sm font-medium px-3 py-2 rounded-lg" style="background: #fff; border: 1px solid var(--pam-line); color: var(--pam-muted);">Voir la fiche →</a>
    <?php endif; ?>
</div>

<?php if ($msg): ?>
    <div class="mb-4 p-3 rounded-lg text-sm" style="<?= $msgType === 'err' ? 'background:#fee2e2;color:#991b1b;border:1px solid #fca5a5;' : 'background:rgba(212,165,116,0.45);color:var(--ink-2);border:1px solid rgba(74,44,20,0.2);' ?>">
        <?= htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<form method="POST" class="space-y-6">
    <?= CSRF::field() ?>

    <!-- Identité -->
    <div class="pam-card p-6">
        <h2 class="font-bold text-lg mb-4 display" style="font-weight: 500;">Identité</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wide mb-1" style="color: var(--pam-muted);">Nom du produit *</label>
                <input type="text" name="name" required value="<?= htmlspecialchars((string)$p['name'], ENT_QUOTES, 'UTF-8') ?>" class="w-full px-3 py-2 rounded-lg border" style="border-color: var(--pam-line);">
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wide mb-1" style="color: var(--pam-muted);">Slug URL *</label>
                <input type="text" name="slug" required value="<?= htmlspecialchars((string)$p['slug'], ENT_QUOTES, 'UTF-8') ?>" pattern="[a-z0-9\-]+" class="w-full px-3 py-2 rounded-lg border font-mono text-sm" style="border-color: var(--pam-line);">
                <p class="text-xs mt-1" style="color: var(--pam-muted);">URL : /tests/<strong><?= htmlspecialchars((string)$p['slug'], ENT_QUOTES, 'UTF-8') ?: 'mon-slug' ?></strong></p>
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wide mb-1" style="color: var(--pam-muted);">Marque</label>
                <input type="text" name="brand" value="<?= htmlspecialchars((string)$p['brand'], ENT_QUOTES, 'UTF-8') ?>" class="w-full px-3 py-2 rounded-lg border" style="border-color: var(--pam-line);">
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wide mb-1" style="color: var(--pam-muted);">Statut</label>
                <select name="status" class="w-full px-3 py-2 rounded-lg border bg-white" style="border-color: var(--pam-line);">
                    <option value="draft" <?= $p['status'] === 'draft' ? 'selected' : '' ?>>Brouillon</option>
                    <option value="published" <?= $p['status'] === 'published' ? 'selected' : '' ?>>Publié</option>
                    <option value="archived" <?= $p['status'] === 'archived' ? 'selected' : '' ?>>Archivé</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Amazon -->
    <div class="pam-card p-6">
        <h2 class="font-bold text-lg mb-4 display" style="font-weight: 500;">Amazon (affiliation)</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wide mb-1" style="color: var(--pam-muted);">ASIN</label>
                <input type="text" name="asin" value="<?= htmlspecialchars((string)$p['asin'], ENT_QUOTES, 'UTF-8') ?>" maxlength="10" pattern="[A-Z0-9]{10}" class="w-full px-3 py-2 rounded-lg border font-mono" style="border-color: var(--pam-line);" placeholder="B0XXXXXXXX">
                <p class="text-xs mt-1" style="color: var(--pam-muted);">10 caractères, depuis l'URL Amazon (/dp/<strong>B0XXXXXXXX</strong>/)</p>
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wide mb-1" style="color: var(--pam-muted);">URL Amazon (fallback)</label>
                <input type="url" name="amazon_url" value="<?= htmlspecialchars((string)$p['amazon_url'], ENT_QUOTES, 'UTF-8') ?>" class="w-full px-3 py-2 rounded-lg border text-sm" style="border-color: var(--pam-line);" placeholder="https://www.amazon.fr/...">
                <p class="text-xs mt-1" style="color: var(--pam-muted);">Optionnel si ASIN renseigné. Le tag est ajouté automatiquement.</p>
            </div>
        </div>
    </div>

    <!-- Specs -->
    <div class="pam-card p-6">
        <h2 class="font-bold text-lg mb-4 display" style="font-weight: 500;">Spécifications techniques</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wide mb-1" style="color: var(--pam-muted);">Technologie</label>
                <select name="technology" class="w-full px-3 py-2 rounded-lg border bg-white" style="border-color: var(--pam-line);">
                    <?php foreach (['co2'=>'CO2 / Propane','uv'=>'UV LED','propane'=>'Propane','aspirant'=>'Aspirant','solaire'=>'Solaire','larvaire'=>'Anti-ponte','combine'=>'Combiné'] as $k=>$v): ?>
                        <option value="<?= $k ?>" <?= $p['technology'] === $k ? 'selected' : '' ?>><?= $v ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wide mb-1" style="color: var(--pam-muted);">Surface (m²)</label>
                <input type="number" name="surface_m2" value="<?= htmlspecialchars((string)$p['surface_m2'], ENT_QUOTES, 'UTF-8') ?>" min="0" class="w-full px-3 py-2 rounded-lg border" style="border-color: var(--pam-line);">
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wide mb-1" style="color: var(--pam-muted);">Bruit (dB)</label>
                <input type="text" name="noise_db" value="<?= htmlspecialchars((string)$p['noise_db'], ENT_QUOTES, 'UTF-8') ?>" class="w-full px-3 py-2 rounded-lg border" style="border-color: var(--pam-line);" placeholder="22.5">
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wide mb-1" style="color: var(--pam-muted);">Autonomie (h)</label>
                <input type="number" name="autonomy_hours" value="<?= htmlspecialchars((string)$p['autonomy_hours'], ENT_QUOTES, 'UTF-8') ?>" min="0" class="w-full px-3 py-2 rounded-lg border" style="border-color: var(--pam-line);">
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wide mb-1" style="color: var(--pam-muted);">Prix (€)</label>
                <input type="text" name="price_eur" value="<?= htmlspecialchars((string)$p['price_eur'], ENT_QUOTES, 'UTF-8') ?>" class="w-full px-3 py-2 rounded-lg border" style="border-color: var(--pam-line);" placeholder="229.00">
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wide mb-1" style="color: var(--pam-muted);">Note (/10)</label>
                <input type="text" name="rating" value="<?= htmlspecialchars((string)$p['rating'], ENT_QUOTES, 'UTF-8') ?>" class="w-full px-3 py-2 rounded-lg border" style="border-color: var(--pam-line);" placeholder="9.1">
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wide mb-1" style="color: var(--pam-muted);">Rang top</label>
                <input type="number" name="rank_global" value="<?= htmlspecialchars((string)$p['rank_global'], ENT_QUOTES, 'UTF-8') ?>" min="1" max="20" class="w-full px-3 py-2 rounded-lg border" style="border-color: var(--pam-line);" placeholder="1">
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wide mb-1" style="color: var(--pam-muted);">Usage cible</label>
                <select name="target_use" class="w-full px-3 py-2 rounded-lg border bg-white" style="border-color: var(--pam-line);">
                    <option value="">—</option>
                    <?php foreach (['terrasse'=>'Terrasse','jardin'=>'Jardin','chambre'=>'Chambre','tigre'=>'Anti-tigre','pro'=>'Pro / restauration'] as $k=>$v): ?>
                        <option value="<?= $k ?>" <?= $p['target_use'] === $k ? 'selected' : '' ?>><?= $v ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>

    <!-- Verdict / pitch -->
    <div class="pam-card p-6">
        <h2 class="font-bold text-lg mb-4 display" style="font-weight: 500;">Verdict éditorial</h2>
        <div class="space-y-4">
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wide mb-1" style="color: var(--pam-muted);">Badge</label>
                <input type="text" name="badge" value="<?= htmlspecialchars((string)$p['badge'], ENT_QUOTES, 'UTF-8') ?>" class="w-full px-3 py-2 rounded-lg border" style="border-color: var(--pam-line);" placeholder="Meilleur global / Rapport qualité-prix / Anti-tigre longue durée …">
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wide mb-1" style="color: var(--pam-muted);">Verdict (1 phrase)</label>
                <input type="text" name="verdict" value="<?= htmlspecialchars((string)$p['verdict'], ENT_QUOTES, 'UTF-8') ?>" maxlength="255" class="w-full px-3 py-2 rounded-lg border" style="border-color: var(--pam-line);">
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wide mb-1" style="color: var(--pam-muted);">Pitch (2-3 phrases descriptives)</label>
                <textarea name="pitch" rows="4" class="w-full px-3 py-2 rounded-lg border" style="border-color: var(--pam-line);"><?= htmlspecialchars((string)$p['pitch'], ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>
        </div>
    </div>

    <!-- Pros / cons -->
    <div class="pam-card p-6">
        <h2 class="font-bold text-lg mb-4 display" style="font-weight: 500;">Points forts &amp; faibles</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wide mb-1" style="color: var(--pam-muted);">Pros (un par ligne)</label>
                <textarea name="pros" rows="6" class="w-full px-3 py-2 rounded-lg border text-sm" style="border-color: var(--pam-line);" placeholder="Surface vraiment grande&#10;Plébiscité contre Aedes albopictus&#10;Robuste sur la durée"><?= htmlspecialchars($prosTxt, ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wide mb-1" style="color: var(--pam-muted);">Cons (un par ligne)</label>
                <textarea name="cons" rows="6" class="w-full px-3 py-2 rounded-lg border text-sm" style="border-color: var(--pam-line);" placeholder="Investissement initial élevé&#10;80–150 €/an de propane"><?= htmlspecialchars($consTxt, ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>
        </div>
    </div>

    <!-- Image -->
    <div class="pam-card p-6">
        <h2 class="font-bold text-lg mb-4 display" style="font-weight: 500;">Image (optionnel)</h2>
        <input type="url" name="image_url" value="<?= htmlspecialchars((string)$p['image_url'], ENT_QUOTES, 'UTF-8') ?>" class="w-full px-3 py-2 rounded-lg border text-sm" style="border-color: var(--pam-line);" placeholder="https://...jpg ou /assets/img/produits/mosquito-magnet.jpg">
        <p class="text-xs mt-1" style="color: var(--pam-muted);">Si vide, un visuel SVG abstrait est généré automatiquement. Évite les images Amazon directes (droits d'auteur).</p>
    </div>

    <!-- Submit -->
    <div class="flex items-center justify-end gap-3 sticky bottom-4">
        <a href="/admin/products.php" class="text-sm font-medium px-4 py-2.5" style="color: var(--pam-muted);">Annuler</a>
        <button type="submit" class="pam-btn-primary" style="padding: 0.7rem 1.5rem; font-size: 0.95rem;">
            <?= $isNew ? 'Créer le produit' : 'Enregistrer les modifications' ?>
        </button>
    </div>
</form>

<?php admin_footer(); ?>
