<?php

namespace App\Controller;

use App\Repository\ArticleRepository;
use App\Repository\CategoryRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(ArticleRepository $articleRepository, CategoryRepository $categoryRepository): Response
    {
        $latestArticles = $articleRepository->findBy(
            ['published' => true],
            ['createdAt' => 'DESC'],
            3
        );

        $categories = $categoryRepository->findRoots();

        return $this->render('home/index.html.twig', [
            'latestArticles' => $latestArticles,
            'categories' => $categories,
        ]);
    }
}
