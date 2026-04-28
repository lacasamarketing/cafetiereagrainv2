<?php
// Article model: chargement depuis DB, CRUD, helpers.

declare(strict_types=1);

namespace App\Core;

use PDO;

final class Article
{
    public int $id;
    public string $slug;
    public string $title;
    public string $description;
    public string $keywordTarget;
    public string $cluster;
    public string $persona;
    public string $contentHtml;
    public ?string $featuredImage;
    public int $readingTime;
    public string $status;
    public ?string $publishAt;
    public ?int $authorId;
    public int $viewsCount;
    public string $createdAt;
    public string $updatedAt;

    public function __construct(array $row)
    {
        $this->id            = (int)$row['id'];
        $this->slug          = $row['slug'];
        $this->title         = $row['title'];
        $this->description   = $row['description'];
        $this->keywordTarget = $row['keyword_target'];
        $this->cluster       = $row['cluster'] ?? 'general';
        $this->persona       = $row['persona'] ?? 'tous';
        $this->contentHtml   = $row['content_html'];
        $this->featuredImage = $row['featured_image'] ?: null;
        $this->readingTime   = (int)($row['reading_time'] ?? 5);
        $this->status        = $row['status'] ?? 'draft';
        $this->publishAt     = $row['publish_at'] ?? null;
        $this->authorId      = isset($row['author_id']) ? (int)$row['author_id'] : null;
        $this->viewsCount    = (int)($row['views_count'] ?? 0);
        $this->createdAt     = $row['created_at'] ?? '';
        $this->updatedAt     = $row['updated_at'] ?? '';
    }

    public static function findBySlug(string $slug, bool $publishedOnly = true): ?self
    {
        $sql = 'SELECT * FROM articles WHERE slug = ?';
        $params = [$slug];
        if ($publishedOnly) {
            $sql .= ' AND status = "published" AND (publish_at IS NULL OR publish_at <= NOW())';
        }
        $sql .= ' LIMIT 1';
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ? new self($row) : null;
    }

    public static function findById(int $id): ?self
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM articles WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? new self($row) : null;
    }

    public static function listPublished(int $limit = 20, int $offset = 0): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM articles
             WHERE status = "published" AND (publish_at IS NULL OR publish_at <= NOW())
             ORDER BY COALESCE(publish_at, created_at) DESC
             LIMIT :lim OFFSET :off'
        );
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $out = [];
        foreach ($stmt->fetchAll() as $row) {
            $out[] = new self($row);
        }
        return $out;
    }

    public static function listAll(int $limit = 100): array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM articles ORDER BY created_at DESC LIMIT :lim');
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $out = [];
        foreach ($stmt->fetchAll() as $row) {
            $out[] = new self($row);
        }
        return $out;
    }

    public static function countPublished(): int
    {
        $stmt = Database::pdo()->query(
            'SELECT COUNT(*) FROM articles
             WHERE status = "published" AND (publish_at IS NULL OR publish_at <= NOW())'
        );
        return (int)$stmt->fetchColumn();
    }

    public static function listRelated(int $excludeId, string $cluster, int $limit = 3): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM articles
             WHERE id != :ex AND cluster = :cl AND status = "published"
             AND (publish_at IS NULL OR publish_at <= NOW())
             ORDER BY COALESCE(publish_at, created_at) DESC LIMIT :lim'
        );
        $stmt->bindValue(':ex', $excludeId, PDO::PARAM_INT);
        $stmt->bindValue(':cl', $cluster, PDO::PARAM_STR);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $out = [];
        foreach ($stmt->fetchAll() as $row) {
            $out[] = new self($row);
        }
        return $out;
    }

    public static function create(array $data): int
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare(
            'INSERT INTO articles (slug, title, description, keyword_target, cluster, persona,
                                   content_html, featured_image, reading_time, status, publish_at, author_id)
             VALUES (:slug, :title, :description, :keyword_target, :cluster, :persona,
                     :content_html, :featured_image, :reading_time, :status, :publish_at, :author_id)'
        );
        $stmt->execute([
            ':slug'           => $data['slug'],
            ':title'          => $data['title'],
            ':description'    => $data['description'],
            ':keyword_target' => $data['keyword_target'],
            ':cluster'        => $data['cluster'] ?? 'general',
            ':persona'        => $data['persona'] ?? 'tous',
            ':content_html'   => $data['content_html'],
            ':featured_image' => $data['featured_image'] ?? null,
            ':reading_time'   => (int)($data['reading_time'] ?? 5),
            ':status'         => $data['status'] ?? 'draft',
            ':publish_at'     => $data['publish_at'] ?? null,
            ':author_id'      => $data['author_id'] ?? null,
        ]);
        return (int)$pdo->lastInsertId();
    }

    public static function update(int $id, array $data): bool
    {
        $stmt = Database::pdo()->prepare(
            'UPDATE articles SET slug = :slug, title = :title, description = :description,
                                 keyword_target = :keyword_target, cluster = :cluster, persona = :persona,
                                 content_html = :content_html, featured_image = :featured_image,
                                 reading_time = :reading_time, status = :status, publish_at = :publish_at
             WHERE id = :id LIMIT 1'
        );
        return $stmt->execute([
            ':id'             => $id,
            ':slug'           => $data['slug'],
            ':title'          => $data['title'],
            ':description'    => $data['description'],
            ':keyword_target' => $data['keyword_target'],
            ':cluster'        => $data['cluster'] ?? 'general',
            ':persona'        => $data['persona'] ?? 'tous',
            ':content_html'   => $data['content_html'],
            ':featured_image' => $data['featured_image'] ?? null,
            ':reading_time'   => (int)($data['reading_time'] ?? 5),
            ':status'         => $data['status'] ?? 'draft',
            ':publish_at'     => $data['publish_at'] ?? null,
        ]);
    }

    public static function delete(int $id): bool
    {
        $stmt = Database::pdo()->prepare('DELETE FROM articles WHERE id = ? LIMIT 1');
        return $stmt->execute([$id]);
    }

    public static function slugify(string $text): string
    {
        $text = mb_strtolower($text, 'UTF-8');
        // Remove diacritics
        if (function_exists('iconv')) {
            $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
            if ($converted !== false) {
                $text = $converted;
            }
        }
        $text = preg_replace('/[^a-z0-9]+/', '-', $text);
        $text = trim($text, '-');
        return substr($text, 0, 190);
    }

    public function url(): string
    {
        return '/blog/' . $this->slug;
    }

    /**
     * URL du cover image (SEO : nom de fichier = slug de l'article)
     * Si featuredImage est defini (upload admin), on l'utilise.
     * Sinon, fallback sur le SVG auto-genere /blog/{slug}.svg
     */
    public function coverUrl(): string
    {
        if ($this->featuredImage) {
            return $this->featuredImage;
        }
        return '/blog/' . $this->slug . '.svg';
    }

    public function ctaUrl(string $baseAffiliateUrl): string
    {
        $sep = strpos($baseAffiliateUrl, '?') !== false ? '&' : '?';
        return $baseAffiliateUrl . $sep
            . 'utm_source=redactionavecia'
            . '&utm_medium=blog'
            . '&utm_campaign=' . urlencode($this->slug);
    }

    public function formattedDate(): string
    {
        $dateStr = $this->publishAt ?: $this->createdAt;
        if (!$dateStr) return '';
        $ts = strtotime($dateStr);
        if (!$ts) return $dateStr;
        $months = [1=>'janvier',2=>'février',3=>'mars',4=>'avril',5=>'mai',6=>'juin',
                   7=>'juillet',8=>'août',9=>'septembre',10=>'octobre',11=>'novembre',12=>'décembre'];
        return (int)date('j', $ts) . ' ' . $months[(int)date('n', $ts)] . ' ' . date('Y', $ts);
    }

    public function incrementViews(): void
    {
        Database::pdo()->prepare('UPDATE articles SET views_count = views_count + 1 WHERE id = ?')
            ->execute([$this->id]);
    }
}
