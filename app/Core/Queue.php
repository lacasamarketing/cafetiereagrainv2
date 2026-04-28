<?php
// Queue des mots-cles a traiter : pick next, mark done/failed, add.

declare(strict_types=1);

namespace App\Core;

use PDO;

final class Queue
{
    public static function pickNext(): ?array
    {
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->query(
                "SELECT * FROM article_queue
                 WHERE status = 'pending'
                 ORDER BY priority DESC, created_at ASC
                 LIMIT 1 FOR UPDATE"
            );
            $row = $stmt->fetch();
            if (!$row) {
                $pdo->commit();
                return null;
            }
            $pdo->prepare(
                "UPDATE article_queue
                 SET status = 'in_progress', last_attempt_at = NOW(), attempts = attempts + 1
                 WHERE id = ?"
            )->execute([$row['id']]);
            $pdo->commit();
            return $row;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function markDone(int $queueId, int $articleId): void
    {
        Database::pdo()->prepare(
            "UPDATE article_queue SET status = 'done', article_id = ? WHERE id = ?"
        )->execute([$articleId, $queueId]);
    }

    public static function markFailed(int $queueId, string $reason = ''): void
    {
        Database::pdo()->prepare(
            "UPDATE article_queue SET status = 'failed', notes = ? WHERE id = ?"
        )->execute([substr($reason, 0, 500), $queueId]);
    }

    public static function reset(int $queueId): void
    {
        Database::pdo()->prepare(
            "UPDATE article_queue SET status = 'pending', attempts = 0, notes = NULL WHERE id = ?"
        )->execute([$queueId]);
    }

    public static function add(array $data): int
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare(
            'INSERT INTO article_queue (keyword_target, title_hint, cluster, persona, priority, status)
             VALUES (:kw, :hint, :cluster, :persona, :priority, "pending")'
        );
        $stmt->execute([
            ':kw'       => trim($data['keyword_target']),
            ':hint'     => $data['title_hint'] ?? null,
            ':cluster'  => $data['cluster'] ?? 'general',
            ':persona'  => $data['persona'] ?? 'tous',
            ':priority' => (int)($data['priority'] ?? 5),
        ]);
        return (int)$pdo->lastInsertId();
    }

    public static function delete(int $id): void
    {
        Database::pdo()->prepare('DELETE FROM article_queue WHERE id = ?')->execute([$id]);
    }

    public static function all(): array
    {
        return Database::pdo()->query(
            'SELECT * FROM article_queue ORDER BY status, priority DESC, created_at ASC'
        )->fetchAll();
    }

    public static function stats(): array
    {
        $stmt = Database::pdo()->query(
            "SELECT status, COUNT(*) AS cnt FROM article_queue GROUP BY status"
        );
        $result = ['pending' => 0, 'in_progress' => 0, 'done' => 0, 'failed' => 0];
        foreach ($stmt->fetchAll() as $row) {
            $result[$row['status']] = (int)$row['cnt'];
        }
        return $result;
    }

    public static function isSlugTaken(string $slug): bool
    {
        $stmt = Database::pdo()->prepare('SELECT COUNT(*) FROM articles WHERE slug = ?');
        $stmt->execute([$slug]);
        return (int)$stmt->fetchColumn() > 0;
    }

    public static function isKeywordTreated(string $keyword): bool
    {
        $stmt = Database::pdo()->prepare(
            'SELECT COUNT(*) FROM articles WHERE keyword_target = ?'
        );
        $stmt->execute([trim($keyword)]);
        return (int)$stmt->fetchColumn() > 0;
    }
}
