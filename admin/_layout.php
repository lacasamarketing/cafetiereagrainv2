<?php
// Layout commun admin : sidebar + topbar + helpers admin_header / admin_footer
declare(strict_types=1);

function admin_header(string $title = 'Admin'): void {
    $pageTitle = $title . ' · Admin cafetiereagrain.fr';
    ?><!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
<script src="https://cdn.tailwindcss.com"></script>
<style>
    :root {
        --pam-cream: #faf6ef; --pam-cream-2: #f1e9d8;
        --pam-ink: #1f1208; --pam-ink-2: #5a4a36;
        --pam-line: #e6dec9; --pam-amber: #c89968;
    }
    body { background: var(--pam-cream); color: var(--pam-ink); font-family: 'Inter', system-ui, sans-serif; }
    .pam-sidebar { background: #1f1208; color: #fff; }
    .pam-sidebar a { color: #faf6ef; }
    .pam-sidebar a:hover, .pam-sidebar a.is-active { background: rgba(200,153,104,0.15); color: #fff; }
</style>
</head>
<body>
<div class="flex min-h-screen">
    <!-- SIDEBAR -->
    <aside class="pam-sidebar w-64 flex-shrink-0 flex flex-col">
        <div class="p-6 border-b border-amber-900/40">
            <a href="/admin/" class="flex items-center gap-3 font-extrabold text-lg">
                <span class="w-10 h-10 rounded-xl flex items-center justify-center" style="background: var(--pam-amber);">
                    <svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" width="24" height="24">
                        <ellipse cx="32" cy="38" rx="14" ry="18" fill="#1f1208"/>
                        <path d="M 32 24 Q 28 30 32 38 Q 36 46 32 52" fill="none" stroke="#c89968" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                </span>
                <span>Cafetiere<br><span class="text-xs font-normal opacity-70">a grain · admin</span></span>
            </a>
        </div>
        <nav class="flex-1 p-4 space-y-1 text-sm">
            <a href="/admin/" class="block px-3 py-2 rounded-lg">Tableau de bord</a>
            <a href="/admin/articles.php" class="block px-3 py-2 rounded-lg">Articles</a>
            <a href="/admin/products.php" class="block px-3 py-2 rounded-lg">Produits</a>
            <a href="/admin/queue.php" class="block px-3 py-2 rounded-lg">Queue mots-cles</a>
            <a href="/admin/run-article.php" class="block px-3 py-2 rounded-lg">Generer un article</a>
            <a href="/admin/sync-now.php" class="block px-3 py-2 rounded-lg">Sync Amazon</a>
        </nav>
        <div class="p-4 border-t border-amber-900/40 text-xs">
            <a href="/admin/logout.php" class="opacity-70 hover:opacity-100">Deconnexion</a>
        </div>
    </aside>
    <!-- MAIN -->
    <main class="flex-1 p-8 overflow-x-auto">
<?php
}

function admin_footer(): void {
    ?>
    </main>
</div>
</body>
</html>
<?php
}
