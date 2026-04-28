<?php
// PDO wrapper singleton. Chargement de la config et connexion MySQL.

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;

final class Database
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $config = self::loadConfig();
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            $config['db']['host'],
            $config['db']['name'],
            $config['db']['charset'] ?? 'utf8mb4'
        );

        try {
            self::$pdo = new PDO($dsn, $config['db']['user'], $config['db']['pass'], [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8mb4' COLLATE 'utf8mb4_unicode_ci'",
            ]);
        } catch (PDOException $ex) {
            error_log('DB connection failed: ' . $ex->getMessage());
            throw new \RuntimeException('Base de donnees indisponible.');
        }

        return self::$pdo;
    }

    public static function loadConfig(): array
    {
        $configFile  = dirname(__DIR__, 2) . '/config/config.php';
        $exampleFile = dirname(__DIR__, 2) . '/config/config.example.php';
        if (is_file($configFile)) {
            return require $configFile;
        }
        if (is_file($exampleFile)) {
            return require $exampleFile;
        }
        throw new \RuntimeException('Configuration absente.');
    }
}
