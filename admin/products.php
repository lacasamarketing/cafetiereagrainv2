<?php
// Admin produits cafetiereagrain.fr — liste + filtres

declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';
require __DIR__ . '/_layout.php';

use App\Core\Database;

$pdo = Database::pdo();

$status = $_GET['status'] ?? 'all';
$where = '';
$params = [];
if (in_array($status, ['published', 'draft', 'archived'], true)) {
    $where = 'WHERE status = ?';
    $params = [$status];
}

$products = $pdo->prepare("SELECT id, slug, name, brand, asin, technology, surface_m2, price_eur, rating, rank_global, badge, target_use, status, updated_at FROM products $where ORDER BY rank_global IS NULL ASC, rank_global ASC, name ASC");
$products->execute($params);
$products = $products->fetchAll();

$counts = [];
foreach (['all', 'published', 'draft', 'archived'] as $s) {
    if ($s === 'all') {
        $counts[$s] = (int)$pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
    } else {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM products WHERE status = ?');
        $stmt->execute([$s]);
        $counts[$s] = (int)$stmt->fetchColumn();
    }
}

admin_header('Produits');
?>

<div class="mb-8 flex items-end justify-between flex-wrap gap-4">
    <div>
        <h1 class="display" style="font-size: 2.25rem; font-weight: 500; letter-spacing: -0.02em;">Produits</h1>
        <p style="color: var(--pam-muted); margin-top: 0.25rem;">Catalogue des cafetières à grain affichés sur le site et liés à l'affiliation Amazon.</p>
    </div>
    <a href="/admin/product-edit.php" class="pam-btn-primary">+ Nouveau produit</a>
</div>

<!-- Filtres status -->
<div class="flex flex-wrap gap-2 mb-6">
    <?php foreach ([['all','Tous'],['published','Publiés'],['draft','Brouillons'],['archived','Archivés']] as [$key, $label]): ?>
        <a href="?status=<?= $key ?>" class="px-3 py-1.5 rounded-full text-sm font-medium transition <?= $status === $key ? 'pam-status-published' : '' ?>" style="<?= $status === $key ? '' : 'background: #fff; color: var(--pam-muted); border: 1px solid var(--pam-line);' ?>">
            <?= $label ?> <span class="opacity-60">(<?= $counts[$key] ?>)</span>
        </a>
    <?php endforeach; ?>
</div>

<div class="pam-card overflow-hidden">
    <?php if (empty($products)): ?>
        <div class="text-center py-16" style="color: var(--pam-muted);">
            <p class="mb-4">Aucun produit pour ce filtre.</p>
            <a href="/admin/product-edit.php" class="pam-btn-primary">Ajouter le premier produit</a>
        </div>
    <?php else: ?>
        <table class="w-full text-sm">
            <thead>
                <tr style="background: var(--pam-cream); border-bottom: 1px solid var(--pam-line);">
                    <th class="text-left px-4 py-3 font-semibold uppercase text-xs tracking-wide" style="color: var(--pam-muted);">Rang</th>
                    <th class="text-left px-4 py-3 font-semibold uppercase text-xs tracking-wide" style="color: var(--pam-muted);">Modèle</th>
                    <th class="text-left px-4 py-3 font-semibold uppercase text-xs tracking-wide" style="color: var(--pam-muted);">Techno</th>
                    <th class="text-left px-4 py-3 font-semibold uppercase text-xs tracking-wide" style="color: var(--pam-muted);">ASIN</th>
                    <th class="text-right px-4 py-3 font-semibold uppercase text-xs tracking-wide" style="color: var(--pam-muted);">Prix</th>
                    <th class="text-right px-4 py-3 font-semibold uppercase text-xs tracking-wide" style="color: var(--pam-muted);">Note</th>
                    <th class="text-left px-4 py-3 font-semibold uppercase text-xs tracking-wide" style="color: var(--pam-muted);">Statut</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $p): ?>
                    <tr style="border-bottom: 1px solid var(--pam-line);" class="hover:bg-white">
                        <td class="px-4 py-3">
                            <?php if (!empty($p['rank_global'])): ?>
                                <span class="display" style="color: var(--forest); font-size: 1.15rem; font-weight: 500;">#<?= (int)$p['rank_global'] ?></span>
                            <?php else: ?>
                                <span style="color: var(--pam-muted); font-size: 0.85rem;">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3">
                            <div class="font-medium"><?= htmlspecialchars((string)$p['name'], ENT_QUOTES, 'UTF-8') ?></div>
                            <div class="text-xs mt-0.5" style="color: var(--pam-muted);">
                                <?php if (!empty($p['brand'])): ?><?= htmlspecialchars((string)$p['brand'], ENT_QUOTES, 'UTF-8') ?> · <?php endif; ?>
                                <?= htmlspecialchars((string)$p['slug'], ENT_QUOTES, 'UTF-8') ?>
                                <?php if (!empty($p['badge'])): ?>
                                    <span class="ml-1 inline-block px-2 py-0.5 rounded text-[10px] font-bold pam-status-published"><?= htmlspecialchars((string)$p['badge'], ENT_QUOTES, 'UTF-8') ?></span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-xs" style="color: var(--pam-muted);"><?= htmlspecialchars((string)$p['technology'], ENT_QUOTES, 'UTF-8') ?> · <?= (int)$p['surface_m2'] ?> m²</td>
                        <td class="px-4 py-3 font-mono text-xs">
                            <?php if (!empty($p['asin']) && !str_contains((string)$p['asin'], 'X')): ?>
                                <span style="color: var(--forest);" title="ASIN configuré"><?= htmlspecialchars((string)$p['asin'], ENT_QUOTES, 'UTF-8') ?></span>
                            <?php else: ?>
                                <span style="color: #c97a1e;" title="ASIN placeholder à remplacer">⚠ <?= htmlspecialchars((string)($p['asin'] ?: 'vide'), ENT_QUOTES, 'UTF-8') ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-right font-medium"><?= number_format((float)$p['price_eur'], 0, ',', ' ') ?> €</td>
                        <td class="px-4 py-3 text-right"><span style="color: var(--forest); font-weight: 600;"><?= number_format((float)$p['rating'], 1, ',', '') ?></span><span style="color: var(--pam-muted); font-size: 0.75rem;">/5</span></td>
                        <td class="px-4 py-3">
                            <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold uppercase <?php
                                echo match($p['status']) {
                                    'published' => 'pam-status-published',
                                    'draft' => 'pam-status-draft',
                                    'archived' => '',
                                    default => ''
                                };
                            ?>" style="<?= $p['status'] === 'archived' ? 'background:#e2e8f0;color:#475569;' : '' ?>">
                                <?= htmlspecialchars((string)$p['status'], ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            <a href="/admin/product-edit.php?id=<?= (int)$p['id'] ?>" class="text-sm font-medium" style="color: var(--forest);">Éditer</a>
                            <?php if (!empty($p['asin']) && !str_contains((string)$p['asin'], 'X')): ?>
                                <span class="mx-1" style="color: var(--pam-line);">·</span>
                                <a href="https://www.amazon.fr/dp/<?= htmlspecialchars((string)$p['asin'], ENT_QUOTES, 'UTF-8') ?>?tag=lacasamarke08-21" target="_blank" rel="noopener" class="text-sm font-medium" style="color: #c97a1e;" title="Vérifier la correspondance du match Amazon">Amazon ↗</a>
                            <?php endif; ?>
                            <span class="mx-1" style="color: var(--pam-line);">·</span>
                            <a href="/tests/<?= htmlspecialchars((string)$p['slug'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" class="text-sm" style="color: var(--pam-muted);">Fiche</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<div class="mt-6 p-4 rounded-xl" style="background: rgba(212,165,116,0.18); border: 1px solid rgba(74,44,20,0.18);">
    <div class="text-sm" style="color: var(--pam-ink);">
        <strong>💡 Vérifier les matchs Rainforest :</strong> clique sur <span style="color:#c97a1e;font-weight:600;">Amazon ↗</span> à droite de chaque produit pour ouvrir la vraie fiche Amazon dans un nouvel onglet. Compare le nom et l'image avec ce qui est en BDD : si ça ne correspond pas, édite le produit pour corriger l'ASIN à la main (10 caractères après <code>/dp/</code> dans l'URL Amazon).
    </div>
</div>

<?php admin_footer(); ?>
