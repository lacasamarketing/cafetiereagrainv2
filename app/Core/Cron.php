<?php
/**
 * Cron — système de routines auto-déclenchées (poor-man's cron, sans cron OVH).
 *
 * Principe :
 *   - Sur chaque page publique, en fin de réponse (shutdown handler), on check
 *     si un job est dû (intervalle écoulé depuis last_run).
 *   - Si oui : on flushe la réponse au visiteur (fastcgi_finish_request) puis
 *     on exécute le job en background, sans le bloquer.
 *   - On stocke last_run en table settings (clé `cron:{jobName}`).
 *
 * Usage :
 *   Cron::register('amazon_sync', 7 * 86400, function () {
 *       (new AmazonSync())->run();
 *   });
 *   Cron::tick();
 *
 * Avantages :
 *   - Aucune config OVH (pas de tâche planifiée à mettre dans le manager)
 *   - Pas de dépendance externe (pas de cron-job.org, pas de GitHub Actions)
 *   - Sécurité : un seul lock via table settings, pas de double-trigger
 *
 * Limites :
 *   - Si le site n'a aucun visiteur pendant 7 jours, le job ne tourne pas.
 *     (Pour cafetiereagrain avec année complète, aucun risque.)
 *   - Le 1er visiteur de la fenêtre paie le coût d'init du job (mais sa réponse
 *     HTTP est déjà rendue grâce à fastcgi_finish_request).
 */

declare(strict_types=1);

namespace App\Core;

use Throwable;

final class Cron
{
    /** @var array<string, array{interval:int, callback:callable}> */
    private static array $jobs = [];

    public static function register(string $name, int $intervalSec, callable $callback): void
    {
        self::$jobs[$name] = ['interval' => $intervalSec, 'callback' => $callback];
    }

    /**
     * À appeler en fin de chaque page publique (via register_shutdown_function par exemple).
     * Vérifie chaque job, si l'un est dû on le lance en background.
     */
    public static function tick(): void
    {
        // Skip sur les pages admin / api (le user qui browse l'admin n'a pas à payer la latence)
        $uri = (string)($_SERVER['REQUEST_URI'] ?? '');
        if (str_starts_with($uri, '/admin') || str_starts_with($uri, '/api/') || str_starts_with($uri, '/go/')) {
            return;
        }

        try {
            foreach (self::$jobs as $name => $job) {
                $key = 'cron:' . $name;
                $last = (int) self::getSetting($key);
                if (time() - $last < $job['interval']) {
                    continue;
                }
                // Marque AVANT exécution pour éviter double-trigger si requête simultanée
                self::setSetting($key, (string)time());
                self::runInBackground($job['callback']);
            }
        } catch (Throwable $e) {
            error_log('Cron::tick failed: ' . $e->getMessage());
        }
    }

    /**
     * Force l'exécution d'un job nommé, peu importe le last_run.
     * Utilisé par /admin/sync-now.php
     */
    public static function runNow(string $name): array
    {
        if (!isset(self::$jobs[$name])) {
            throw new \RuntimeException('Job inconnu : ' . $name);
        }
        $start = microtime(true);
        ob_start();
        try {
            ($self::$jobs[$name]['callback'])();
            $output = ob_get_clean();
            self::setSetting('cron:' . $name, (string)time());
            return ['ok' => true, 'output' => $output, 'elapsed_s' => round(microtime(true) - $start, 1)];
        } catch (Throwable $e) {
            $output = ob_get_clean();
            return ['ok' => false, 'error' => $e->getMessage(), 'output' => $output];
        }
    }

    public static function lastRun(string $name): ?int
    {
        $val = self::getSetting('cron:' . $name);
        return $val ? (int)$val : null;
    }

    /**
     * Lance un callback en background sans bloquer la réponse HTTP en cours.
     */
    private static function runInBackground(callable $cb): void
    {
        // 1. Flushe la réponse au navigateur (FastCGI / FPM)
        if (function_exists('fastcgi_finish_request')) {
            @fastcgi_finish_request();
        } else {
            // Fallback : on signale que la connexion peut être close
            @ob_end_flush();
            @flush();
        }
        ignore_user_abort(true);
        @set_time_limit(180);

        try {
            $cb();
        } catch (Throwable $e) {
            error_log('Cron job failed in background: ' . $e->getMessage());
        }
    }

    private static function getSetting(string $key): string
    {
        try {
            $stmt = Database::pdo()->prepare('SELECT setting_value FROM settings WHERE setting_key = ? LIMIT 1');
            $stmt->execute([$key]);
            return (string)$stmt->fetchColumn();
        } catch (Throwable $e) {
            return '';
        }
    }

    private static function setSetting(string $key, string $value): void
    {
        try {
            $stmt = Database::pdo()->prepare(
                'INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)
                 ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
            );
            $stmt->execute([$key, $value]);
        } catch (Throwable $e) {
            error_log('Cron::setSetting failed: ' . $e->getMessage());
        }
    }
}
