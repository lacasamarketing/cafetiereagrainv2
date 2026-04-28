<?php
// Layout admin commun : header, sidebar, footer
// Usage: require '_layout.php'; puis appeler admin_header() / admin_footer()

declare(strict_types=1);

use App\Core\Auth;

function admin_header(string $pageTitle = 'Admin'): void
{
    Auth::requireLogin();
    $user = Auth::currentUser();
    $currentPath = $_SERVER['REQUEST_URI'] ?? '';
    ?>
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="robots" content="noindex,nofollow">
        <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?> · Admin cafetiereagrain.fr</title>
        <link rel="icon" type="image/svg+xml" href="/assets/img/favicon.svg">
        <script src="https://cdn.tailwindcss.com"></script>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Fraunces:opsz,wght@9..144,400;9..144,500&display=swap" rel="stylesheet">
        <style>
            :root {
                --pam-cream: #f5f3ec;
                --pam-cream-2: #ebe8dd;
                --pam-ink: #0a1f17;
                --forest: #1f5742;
                --ink-2: #154031;
                --pam-lime: #c6e870;
                --pam-muted: #4d6359;
                --pam-line: rgba(74,44,20,0.12);
            }
            body { font-family: 'Inter', -apple-system, sans-serif; background: var(--pam-cream); color: var(--pam-ink); }
            .display { font-family: 'Fraunces', serif; }
            .nav-link { color: var(--pam-muted); }
            .nav-link.active {
                background: var(--pam-ink);
                color: var(--pam-cream);
            }
            .nav-link:not(.active):hover { background: var(--pam-cream-2); color: var(--pam-ink); }
            .pam-sidebar { background: #fff; border-right: 1px solid var(--pam-line); }
            .pam-card { background: #fff; border: 1px solid var(--pam-line); border-radius: 16px; }
            .pam-btn-primary {
                background: var(--pam-ink); color: var(--pam-cream);
                font-weight: 600;
                padding: 0.55rem 1rem;
                border-radius: 9999px;
                font-size: 0.875rem;
                transition: background 0.15s;
            }
            .pam-btn-primary:hover { background: var(--forest); }
            .pam-num-published { color: var(--forest); }
            .pam-num-drafts { color: #c97a1e; }
            .pam-num-total { color: var(--pam-ink); }
            .pam-num-clicks { color: var(--forest); }
            .pam-status-published { background: rgba(212,165,116,0.55); color: var(--ink-2); }
            .pam-status-draft { background: #fff3d6; color: #8a5a14; }
            .pam-status-scheduled { background: #dde9ff; color: #1d4ed8; }
        </style>
    </head>
    <body class="min-h-screen">
        <div class="flex min-h-screen">
            <!-- SIDEBAR -->
            <aside class="pam-sidebar w-64 flex-shrink-0 flex flex-col">
                <div class="p-6 border-b" style="border-color: var(--pam-line);">
                    <a href="/admin/" class="flex items-center gap-3 font-extrabold">
                        <span class="w-9 h-9 rounded-xl flex items-center justify-center" style="background: var(--pam-cream-2);">
                            <svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" width="28" height="28">
                                <circle cx="32" cy="