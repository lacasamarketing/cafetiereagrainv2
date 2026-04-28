<?php
// Verification du token et mise a jour du mot de passe

declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

use App\Core\Database;
use App\Core\CSRF;

$error = null;
$done = false;
$validToken = false;
$userId = null;

$plainToken = (string)($_GET['token'] ?? $_POST['token'] ?? '');
if ($plainToken !== '' && ctype_xdigit($plainToken) && strlen($plainToken) === 64) {
    $tokenHash = hash('sha256', $plainToken);
    try {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare(
            'SELECT pr.id, pr.user_id, pr.expires_at, pr.used_at
             FROM password_resets pr
             WHERE pr.token_hash = ? LIMIT 1'
        );
        $stmt->execute([$tokenHash]);
        $reset = $stmt->fetch();
        if ($reset && empty($reset['used_at']) && strtotime($reset['expires_at']) > time()) {
            $validToken = true;
            $userId = (int)$reset['user_id'];
            $resetId = (int)$reset['id'];
        } else {
            $error = "Ce lien est invalide ou expiré.";
        }
    } catch (Throwable $e) {
        error_log('Reset password error: ' . $e->getMessage());
        $error = "Erreur technique.";
    }
} else {
    $error = "Lien invalide.";
}

if ($validToken && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!CSRF::verify($_POST['_csrf'] ?? null)) {
        $error = "Session invalide. Recharge la page.";
    } else {
        $password = (string)($_POST['password'] ?? '');
        $passwordConfirm = (string)($_POST['password_confirm'] ?? '');

        if (strlen($password) < 12) {
            $error = "Mot de passe : 12 caracteres minimum.";
        } elseif (!preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) ||
                  !preg_match('/[0-9]/', $password) || !preg_match('/[^A-Za-z0-9]/', $password)) {
            $error = "Mot de passe : doit contenir majuscule, minuscule, chiffre et caractere special.";
        } elseif ($password !== $passwordConfirm) {
            $error = "Les mots de passe ne correspondent pas.";
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $pdo->beginTransaction();
            try {
                // Update password
                $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([$hash, $userId]);
                // Mark token used
                $pdo->prepare('UPDATE password_resets SET used_at = NOW() WHERE id = ?')->execute([$resetId]);
                // Invalidate ALL OTHER active tokens for this user
                $pdo->prepare(
                    'UPDATE password_resets SET used_at = NOW()
                     WHERE user_id = ? AND used_at IS NULL AND id != ?'
                )->execute([$userId, $resetId]);
                $pdo->commit();
                $done = true;
            } catch (Throwable $e) {
                $pdo->rollBack();
                error_log('Password update error: ' . $e->getMessage());
                $error = "Erreur technique. Reessaie.";
            }
        }
    }
}
?><!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex,nofollow">
    <title>Nouveau mot de passe - redactionavecia.fr</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>body{font-family:'Inter',-apple-system,sans-serif}</style>
</head>
<body class="bg-slate-50 min-h-screen flex items-center justify-center p-6">
    <div class="w-full max-w-md">
        <div class="text-center mb-8">
            <div class="w-14 h-14 mx-auto rounded-2xl bg-gradient-to-br from-indigo-500 to-pink-500 flex items-center justify-center text-white font-black text-2xl mb-4">r</div>
            <h1 class="text-2xl font-black">Nouveau mot de passe</h1>
        </div>

        <?php if ($done): ?>
            <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-6 text-center">
                <div class="text-3xl mb-3">✅</div>
                <h2 class="font-bold mb-2">Mot de passe mis a jour</h2>
                <p class="text-sm text-slate-600 mb-4">Tu peux te connecter avec ton nouveau mot de passe.</p>
                <a href="/admin/login.php" class="inline-block bg-gradient-to-r from-indigo-600 to-pink-500 text-white font-semibold px-6 py-3 rounded-xl">Se connecter</a>
            </div>
        <?php elseif (!$validToken): ?>
            <div class="bg-red-50 border border-red-200 rounded-xl p-6 text-center">
                <div class="text-3xl mb-3">⚠️</div>
                <h2 class="font-bold mb-2">Lien invalide ou expire</h2>
                <p class="text-sm text-slate-600 mb-4"><?= htmlspecialchars($error ?? '', ENT_QUOTES, 'UTF-8') ?></p>
                <a href="/admin/forgot-password.php" class="inline-block bg-indigo-600 text-white text-sm font-semibold px-4 py-2 rounded-lg">Redemander un lien</a>
            </div>
        <?php else: ?>
            <?php if ($error): ?>
                <div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-4 text-sm text-red-700"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
            <form method="POST" class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm space-y-4">
                <?= CSRF::field() ?>
                <input type="hidden" name="token" value="<?= htmlspecialchars($plainToken, ENT_QUOTES, 'UTF-8') ?>">
                <div>
                    <label class="block text-sm font-semibold mb-1">Nouveau mot de passe</label>
                    <input type="password" name="password" required minlength="12" autofocus
                           class="w-full px-4 py-2.5 rounded-lg border border-slate-300 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 outline-none">
                    <p class="text-xs text-slate-500 mt-1">12 car min, majuscule, minuscule, chiffre, spécial.</p>
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-1">Confirmer</label>
                    <input type="password" name="password_confirm" required minlength="12"
                           class="w-full px-4 py-2.5 rounded-lg border border-slate-300 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 outline-none">
                </div>
                <button type="submit" class="w-full bg-gradient-to-r from-indigo-600 to-pink-500 text-white font-semibold py-3 rounded-xl">Mettre à jour</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
