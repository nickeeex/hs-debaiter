<?php

namespace Jalle19\HsDebaiter\Http;

use Jalle19\HsDebaiter\Repository\ArticleRepository;
use JMS\Serializer\Serializer;
use League\Route\Http\Exception\NotFoundException;
use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ArticleController
{

    private ArticleRepository $articleRepository;
    private Serializer $serializer;

    /**
     * @param ArticleRepository $articleRepository
     * @param Serializer $serializer
     */
    public function __construct(ArticleRepository $articleRepository, Serializer $serializer)
    {
        $this->articleRepository = $articleRepository;
        $this->serializer = $serializer;
    }

    public function getTodaysChangedArticles(ServerRequestInterface $request): ResponseInterface
    {
        $articles = $this->articleRepository->getTodaysChangedArticles();

        $response = (new Response())
            ->withHeader('Content-Type', 'application/json');

        $response->getBody()->write($this->serializer->serialize($articles, 'json'));

        return $response;
    }

    public function getFrequentlyChangedArticles(ServerRequestInterface $request): ResponseInterface
    {
        $articles = $this->articleRepository->getFrequentlyChangedArticles(15);

        $response = (new Response())
            ->withHeader('Content-Type', 'application/json');

        $response->getBody()->write($this->serializer->serialize($articles, 'json'));

        return $response;
    }

    public function getCategoryArticles(ServerRequestInterface $request, array $args): ResponseInterface
    {
        $category = urldecode($args['category']);
        $articles = $this->articleRepository->getCategoryArticles($category, 30);

        $response = (new Response())
            ->withHeader('Content-Type', 'application/json');

        $response->getBody()->write($this->serializer->serialize($articles, 'json'));

        return $response;
    }

    /**
     * @throws NotFoundException
     */
    public function getArticle(ServerRequestInterface $request, array $args): ResponseInterface
    {
        $article = $this->articleRepository->getArticle($args['guid']);

        if (!$article) {
            throw new NotFoundException();
        }

        $articleTitles = $this->articleRepository->getArticleTitles($article->getId());
        $article->setArticleTitles(iterator_to_array($articleTitles));

        $articleTestTitles = $this->articleRepository->getArticleTestTitles($article->getId());
        $article->setArticleTestTitles(iterator_to_array($articleTestTitles));

        $response = (new Response())
            ->withHeader('Content-Type', 'application/json');

        $response->getBody()->write($this->serializer->serialize($article, 'json'));

        return $response;
    }

    public function searchArticles(ServerRequestInterface $request): ResponseInterface
    {
        $queryParams = $request->getQueryParams();
        $query = $queryParams['q'] ?? '';

        $articles = [];
        if (!empty($query)) {
            $articles = iterator_to_array($this->articleRepository->searchArticles($query));
        }

        $response = (new Response())
            ->withHeader('Content-Type', 'application/json');

        $response->getBody()->write($this->serializer->serialize($articles, 'json'));

        return $response;
    }
}
