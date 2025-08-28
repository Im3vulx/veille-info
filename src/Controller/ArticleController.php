<?php

namespace App\Controller;

use App\Entity\Article;
use App\Repository\ArticleRepository;
use App\Repository\CategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/articles')]
final class ArticleController extends AbstractController
{
    #[Route('/', name: 'app_articles')]
    public function index(ArticleRepository $articleRepository, CategoryRepository $categoryRepository, Request $request): Response
    {
        $categories = $categoryRepository->findChildren();
        $selectedCategory = $request->query->get('category');

        if ($selectedCategory) {
            $category = $categoryRepository->find($selectedCategory);
            $articles = $articleRepository->findBy(
                ['category' => $category, 'published' => true],
                ['createdAt' => 'DESC']
            );
        } else {
            $articles = $articleRepository->findBy(['published' => true], ['createdAt' => 'DESC']);
        }

        return $this->render('article/index.html.twig', [
            'articles' => $articles,
            'categories' => $categories,
            'selectedCategory' => $selectedCategory,
        ]);
    }

    #[Route('/{id}', name: 'app_article_show', requirements: ['id' => '\d+'])]
    public function show(Article $article): Response
    {
        if (!$article->isPublished()) {
            throw $this->createNotFoundException('Cet article n\'est pas publié.');
        }

        return $this->render('article/show.html.twig', [
            'article' => $article,
        ]);
    }
}
