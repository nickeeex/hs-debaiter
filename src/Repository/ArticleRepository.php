<?php

declare(strict_types=1);

namespace Jalle19\HsDebaiter\Repository;

use Jalle19\HsDebaiter\Model\Article;
use Jalle19\HsDebaiter\Model\ArticleTestTitle;
use Jalle19\HsDebaiter\Model\ArticleTitle;

class ArticleRepository
{
    private \PDO $pdo;

    /**
     * @param \PDO $pdo
     */
    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getRecentlyAddedArticles(): \Generator
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM articles 
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL 3 HOUR)'
        );

        $stmt->execute();

        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            yield Article::fromDatabaseRow($row);
        }
    }

    public function hasHeadlineVariant(Article $article, int $variantId): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM article_test_titles
             WHERE article_id = :article AND variant_id = :variant
             LIMIT 1'
        );

        $stmt->execute([
            ':article' => $article->getId(),
            ':variant' => $variantId,
        ]);

        return $stmt->rowCount() === 1;
    }

    public function storeHeadlineVariant(Article $article, int $variantId, string $title): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO article_test_titles (article_id, variant_id, title) 
             VALUES (:article, :variant, :title)'
        );

        $stmt->execute([
            ':article' => $article->getId(),
            ':variant' => $variantId,
            ':title' => $title,
        ]);
    }

    public function getTodaysChangedArticles(): \Generator
    {
        $stmt = $this->pdo->prepare(
            'SELECT articles.*, 
                COUNT(DISTINCT article_titles.id) AS num_titles,
                COUNT(DISTINCT article_test_titles.id) AS num_test_titles
             FROM articles
             LEFT OUTER JOIN article_titles ON (article_titles.article_id = articles.id)
             LEFT OUTER JOIN article_test_titles ON (article_test_titles.article_id = articles.id)
             WHERE articles.created_at > (NOW() - INTERVAL 1 DAY)
             GROUP BY articles.id
             HAVING COUNT(DISTINCT article_titles.id) > 1 OR COUNT(DISTINCT article_test_titles.id) > 1
             ORDER BY id DESC'
        );

        $stmt->execute();

        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            yield Article::fromDatabaseRow($row);
        }
    }

    public function getFrequentlyChangedArticles(int $limit): \Generator
    {
        $stmt = $this->pdo->prepare(
            'SELECT articles.*, 
                COUNT(DISTINCT article_titles.id) AS num_titles,
                COUNT(DISTINCT article_test_titles.id) AS num_test_titles
             FROM articles
             LEFT OUTER JOIN article_titles ON (article_titles.article_id = articles.id)
             LEFT OUTER JOIN article_test_titles ON (article_test_titles.article_id = articles.id)
             WHERE articles.created_at > (NOW() - INTERVAL 7 DAY)
             GROUP BY articles.id
             ORDER BY COUNT(article_titles.id) DESC LIMIT :limit'
        );

        $stmt->bindParam(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();

        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            yield Article::fromDatabaseRow($row);
        }
    }

    public function getCategoryArticles(string $category, int $limit): \Generator
    {
        $stmt = $this->pdo->prepare(
            'SELECT articles.*, COUNT(article_titles.id) AS num_titles
             FROM articles
             LEFT OUTER JOIN article_titles ON (article_titles.article_id = articles.id)
             WHERE articles.created_at > (NOW() - INTERVAL 7 DAY)
             AND articles.category = :category
             GROUP BY articles.id
             ORDER BY id DESC
             LIMIT :limit'
        );

        $stmt->bindParam(':category', $category);
        $stmt->bindParam(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();

        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            yield Article::fromDatabaseRow($row);
        }
    }

    public function getArticle(string $guid): ?Article
    {
        $stmt = $this->pdo->prepare(
            'SELECT articles.*,
                 COUNT(DISTINCT article_titles.id) AS num_titles,
                 COUNT(DISTINCT article_test_titles.id) AS num_test_titles
             FROM articles
             LEFT OUTER JOIN article_titles ON (article_titles.article_id = articles.id)
             LEFT OUTER JOIN article_test_titles ON (article_test_titles.article_id = articles.id)
             WHERE guid = :guid
             GROUP BY articles.id'
        );

        $stmt->execute([
            ':guid' => $guid,
        ]);

        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($row === false) {
            return null;
        }

        return Article::fromDatabaseRow($row);
    }

    public function storeArticle(Article $article): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO articles (guid, category, title, url, image_url) 
             VALUES (:guid, :category, :title, :url, :imageUrl)'
        );

        $stmt->execute([
            ':guid' => $article->getGuid(),
            ':category' => $article->getCategory(),
            ':title' => $article->getTitle(),
            ':url' => $article->getUrl(),
            ':imageUrl' => $article->getImageUrl(),
        ]);

        $id = $this->pdo->lastInsertId();
        $article->setId($id);

        $this->storeArticleTitleChange($article, $article->getTitle());
    }

    public function storeArticleTitleChange(Article $article, string $title): void
    {
        // Store the change
        $stmt = $this->pdo->prepare(
            'INSERT INTO article_titles (article_id, title)
             VALUES (:article_id, :title)'
        );

        $stmt->execute([
            ':article_id' => $article->getId(),
            ':title' => $title,
        ]);

        // Update the article itself to have the newest title
        $stmt = $this->pdo->prepare(
            'UPDATE articles SET title = :title WHERE id = :id'
        );

        $stmt->execute([
            ':title' => $title,
            ':id' => $article->getId(),
        ]);
    }

    public function getArticleTitles(int $id): \Generator
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM article_titles 
             WHERE article_id = :article_id
             ORDER BY id DESC'
        );

        $stmt->execute([':article_id' => $id]);

        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            yield ArticleTitle::fromDatabaseRow($row);
        }
    }

    public function getArticleTestTitles(int $id): \Generator
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM article_test_titles 
             WHERE article_id = :article_id
             ORDER BY id DESC'
        );

        $stmt->execute([':article_id' => $id]);

        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            yield ArticleTestTitle::fromDatabaseRow($row);
        }
    }

    public function searchArticles(string $query): \Generator
    {
        // Escape SQL wildcards to prevent unwanted pattern matching
        $escapedQuery = str_replace(['%', '_'], ['\\%', '\\_'], $query);
        $searchTerm = '%' . $escapedQuery . '%';
        
        $stmt = $this->pdo->prepare(
            'SELECT DISTINCT articles.*, 
                COUNT(DISTINCT article_titles.id) AS num_titles,
                COUNT(DISTINCT article_test_titles.id) AS num_test_titles
             FROM articles
             LEFT OUTER JOIN article_titles ON (article_titles.article_id = articles.id)
             LEFT OUTER JOIN article_test_titles ON (article_test_titles.article_id = articles.id)
             WHERE articles.title LIKE :search
                OR EXISTS (
                    SELECT 1 FROM article_titles 
                    WHERE article_titles.article_id = articles.id 
                    AND article_titles.title LIKE :search
                )
                OR EXISTS (
                    SELECT 1 FROM article_test_titles 
                    WHERE article_test_titles.article_id = articles.id 
                    AND article_test_titles.title LIKE :search
                )
             GROUP BY articles.id
             ORDER BY articles.created_at DESC
             LIMIT 50'
        );

        $stmt->execute(['search' => $searchTerm]);

        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            yield Article::fromDatabaseRow($row);
        }
    }
}
