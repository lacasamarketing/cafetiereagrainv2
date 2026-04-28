<?php
// Authentification admin: login, sessions, rate limit.

declare(strict_types=1);

namespace App\Core;

final class Auth
{
    public const MAX_ATTEMPTS = 5;
    public const LOCKOUT_MINUTES = 15;
    public const SESSION_LIFETIME = 7200;

    public static function startSession(): void
    {
        if (session_status() !== PHP_SESSION_NONE) {
            return;
        }
        $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'domain'   => '',
            'secure'   => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_name('PIEGSESS');
        session_start();
    }

    public static function isLoggedIn(): bool
    {
        self::startSession();
        if (empty($_SESSION['user_id']) || empty($_SESSION['auth_time'])) {
            return false;
        }
        if (time() - (int)$_SESSION['auth_time'] > self::SESSION_LIFETIME) {
            self::logout();
            return false;
        }
        // IP/UA pinning (soft)
        $fingerprint = self::fingerprint();
        if (!empty($_SESSION['fingerprint']) && $_SESSION['fingerprint'] !== $fingerprint) {
            self::logout();
            return false;
        }
        return true;
    }

    public static function requireLogin(): void
    {
        if (!self::isLoggedIn()) {
            header('Location: /admin/login');
            exit;
        }
    }

    public static function currentUser(): ?array
    {
        if (!self::isLoggedIn()) {
            return null;
        }
        $stmt = Database::pdo()->prepare('SELECT id, email, name, is_active FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([(int)$_SESSION['user_id']]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public static function login(string $email, string $password): array
    {
        self::startSession();
        $email = strtolower(trim($email));
        $ipHash = self::ipHash();

        if (self::isLockedOut($email, $ipHash)) {
            return ['ok' => false, 'error' => 'Trop de tentatives. Reessaie dans ' . self::LOCKOUT_MINUTES . ' minutes.'];
        }

        $pdo = Database::pdo();
        $stmt = $pdo->prepare('SELECT id, email, password_hash, is_active FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !$user['is_active'] || !password_verify($password, $user['password_hash'])) {
            self::logAttempt($email, $ipHash, false);
            return ['ok' => false, 'error' => 'Identifiants invalides.'];
        }

        // Upgrade hash si besoin
        if (password_needs_rehash($user['password_hash'], PASSWORD_BCRYPT)) {
            $newHash = password_hash($password, PASSWORD_BCRYPT);
            $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([$newHash, $user['id']]);
        }

        self::logAttempt($email, $ipHash, true);
        session_regenerate_id(true);
        $_SESSION['user_id']     = (int)$user['id'];
        $_SESSION['auth_time']   = time();
        $_SESSION['fingerprint'] = self::fingerprint();

        return ['ok' => true, 'user' => $user];
    }

    public static function logout(): void
    {
        self::startSession();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
    }

    private static function isLockedOut(string $email, string $ipHash): bool
    {
        $stmt = Database::pdo()->prepare(
            "SELECT COUNT(*) FROM login_attempts
             WHERE (email = ? OR ip_hash = ?) AND success = 0
             AND attempted_at >= DATE_SUB(NOW(), INTERVAL ? MINUTE)"
        );
        $stmt->execute([$email, $ipHash, self::LOCKOUT_MINUTES]);
        $count = (int)$stmt->fetchColumn();
        return $count >= self::MAX_ATTEMPTS;
    }

    private static function logAttempt(string $email, string $ipHash, bool $success): void
    {
        Database::pdo()->prepare(
            'INSERT INTO login_attempts (email, ip_hash, success) VALUES (?, ?, ?)'
        )->execute([$email, $ipHash, $success ? 1 : 0]);
    }

    private static function ipHash(): string
    {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
        $ip = explode(',', $ip)[0];
        $config = Database::loadConfig();
        $salt = $config['security']['app_key'] ?? 'default-salt';
        return hash('sha256', $ip . '|' . $salt);
    }

    private static function fingerprint(): string
    {
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $config = Database::loadConfig();
        $salt = $config['security']['app_key'] ?? 'default-salt';
        return hash('sha256', $ua . '|' . $salt);
    }
}
