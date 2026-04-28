<?php
// Demande de reset mot de passe - envoie un email avec token

declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

use App\Core\Database;
use App\Core\CSRF;
use App\Core\Mailer;

$done = false;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!CSRF::verify($_POST['_csrf'] ?? null)) {
        $error = "Session invalide. Recharge la page.";
    } else {
        $email = strtolower(trim($_POST['email'] ?? ''));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Email invalide.";
        } else {
            try {
                $pdo = Database::pdo();

                // Rate limiting : max 3 demandes par heure pour cet email
                $stmt = $pdo->prepare(
                    "SELECT COUNT(*) FROM password_resets pr
                     INNER JOIN users u ON u.id = pr.user_id
                     WHERE u.email = ? AND pr.created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)"
                );
                $stmt->execute([$email]);
                $recentCount = (int)$stmt->fetchColumn();

                if ($recentCount >= 3) {
                    // Ne pas reveler - afficher success pareil
                } else {
                    $stmt = $pdo->prepare('SELECT id, name FROM users WHERE email = ? AND is_active = 1 LIMIT 1');
                    $stmt->execute([$email]);
                    $user = $stmt->fetch();

                    if ($user) {
                        // Generer token
                        $plainToken = bin2hex(random_bytes(32));
                        $tokenHash = hash('sha256', $plainToken);
                        $expires = date('Y-m-d H:i:s', time() + 3600); // 1h

                        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
                        $ipHash = hash('sha256', explode(',', $ip)[0]);

                        $ins = $pdo->prepare(
                            'INSERT INTO password_resets (user_id, token_hash, expires_at, ip_hash)
                             VALUES (?, ?, ?, ?)'
                        );
                        $ins->execute([$user['id'], $tokenHash, $expires, $ipHash]);

                        // Envoyer email
                        $resetUrl = 'https://redactionavecia.fr/admin/reset-password.php?token=' . $plainToken;
                        $subject = 'Réinitialisation de ton mot de passe - redactionavecia.fr';
                        $htmlBody = '<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;padding:20px;color:#1e293b">' .
                            '<h2 style="color:#4f46e5">Réinitialisation de mot de passe</h2>' .
                            '<p>Bonjour ' . htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8') . ',</p>' .
                            '<p>Tu as demandé une réinitialisation de ton mot de passe sur <strong>redactionavecia.fr</strong>.</p>' .
                            '<p>Clique sur ce lien (valable 1 heure) :</p>' .
                            '<p><a href="' . htmlspecialchars($resetUrl, ENT_QUOTES, 'UTF-8') . '" style="display:inline-block;background:#4f46e5;color:#fff;padding:12px 24px;border-radius:8px;text-decoration:none;font-weight:600">Réinitialiser mon mot de passe</a></p>' .
                            '<p style="color:#64748b;font-size:14px">Si tu n\'es pas à l\'origine de cette demande, ignore cet email.</p>' .
                            '<p style="color:#64748b;font-size:12px;margin-top:30px">Lien brut : <br>' . htmlspecialchars($resetUrl, ENT_QUOTES, 'UTF-8') . '</p>' .
                            '</div>';

                        Mailer::send($user['email'] ?? $email, $subject, $htmlBody);
                    }
                }
                $done = true;
            } catch (Throwable $e) {
                error_log('Forgot password error: ' . $e->getMessage());
                $error = "Erreur technique. Reessaie dans un instant.";
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
    <title>Mot de passe oublié - redactionavecia.fr</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>body{font-family:'Inter',-apple-system,sans-serif}</style>
</head>
<body class="bg-slate-50 min-h-screen flex items-center justify-center p-6">
    <div class="w-full max-w-md">
        <div class="text-center mb-8">
            <div class="w-14 h-14 mx-auto rounded-2xl bg-gradient-to-br from-indigo-500 to-pink-500 flex items-center justify-center text-white font-black text-2xl mb-4">r</div>
            <h1 class="text-2xl font-black">Mot de passe oublié</h1>
            <p class="text-slate-500 text-sm mt-1">On t'envoie un lien de réinitialisation par email</p>
        </div>

        <?php if ($done): ?>
            <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-6 text-center">
                <div class="text-3xl mb-3">📧</div>
                <h2 class="font-bold mb-2">Email envoyé</h2>
                <p class="text-sm text-slate-600">Si cet email correspond à un compte, un lien de réinitialisation vient d'être envoyé. Vérifie ta boîte (et les spams). Valide 1 heure.</p>
                <a href="/admin/login.php" class="inline-block mt-4 text-indigo-600 font-semibold hover:underline text-sm">← Retour connexion</a>
            </div>
        <?php else: ?>
            <?php if ($error): ?>
                <div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-4 text-sm text-red-700"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
            <form method="POST" class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm space-y-4">
                <?= CSRF::field() ?>
                <div>
                    <label class="block text-sm font-semibold mb-1">Email du compte</label>
                    <input type="email" name="email" required autofocus
                           class="w-full px-4 py-2.5 rounded-lg border border-slate-300 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 outline-none"
                           value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <button type="submit" class="w-full bg-gradient-to-r from-indigo-600 to-pink-500 text-white font-semibold py-3 rounded-xl">Envoyer le lien</button>
            </form>
            <p class="text-center text-xs text-slate-400 mt-4">
                <a href="/admin/login.php" class="hover:text-slate-600">← Retour connexion</a>
            </p>
        <?php endif; ?>
    </div>
</body>
</html>
