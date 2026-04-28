<?php
// Page de connexion admin

declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

use App\Core\Auth;
use App\Core\CSRF;

if (Auth::isLoggedIn()) {
    header('Location: /admin/');
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!CSRF::verify($_POST['_csrf'] ?? null)) {
        $error = "Session invalide. Recharge la page.";
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = (string)($_POST['password'] ?? '');
        $result = Auth::login($email, $password);
        if ($result['ok']) {
            header('Location: /admin/');
            exit;
        }
        $error = $result['error'] ?? 'Erreur inconnue.';
    }
}
?><!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex,nofollow">
    <title>Connexion admin - redactionavecia.fr</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>body{font-family:'Inter',-apple-system,sans-serif}</style>
</head>
<body class="bg-slate-50 min-h-screen flex items-center justify-center p-6">
    <div class="w-full max-w-md">
        <div class="text-center mb-8">
            <div class="w-14 h-14 mx-auto rounded-2xl bg-gradient-to-br from-indigo-500 to-pink-500 flex items-center justify-center text-white font-black text-2xl mb-4">r</div>
            <h1 class="text-2xl font-black">Connexion admin</h1>
            <p class="text-slate-500 text-sm mt-1">redactionavecia.fr</p>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-4 text-sm text-red-700"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <form method="POST" class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm space-y-4">
            <?= CSRF::field() ?>
            <div>
                <label class="block text-sm font-semibold mb-1">Email</label>
                <input type="email" name="email" required autofocus autocomplete="email"
                       class="w-full px-4 py-2.5 rounded-lg border border-slate-300 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 outline-none"
                       value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">Mot de passe</label>
                <input type="password" name="password" required autocomplete="current-password"
                       class="w-full px-4 py-2.5 rounded-lg border border-slate-300 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 outline-none">
            </div>
            <button type="submit" class="w-full bg-gradient-to-r from-indigo-600 to-pink-500 text-white font-semibold py-3 rounded-xl hover:shadow-lg hover:shadow-indigo-500/30 transition">Se connecter</button>
        </form>

        <p class="text-center text-xs text-slate-400 mt-6">
            <a href="/" class="hover:text-slate-600">← Retour au site</a>
        </p>
    </div>
</body>
</html>
