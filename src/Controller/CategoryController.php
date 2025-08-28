<?php

namespace App\Controller;

use App\Entity\Category;
use App\Repository\ArticleRepository;
use App\Repository\CategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CategoryController extends AbstractController
{
    #[Route('/category', name: 'app_category')]
    public function index(CategoryRepository $categoryRepository): Response
    {
        $categories = $categoryRepository->findRoots();

        return $this->render('category/index.html.twig', [
            'categories' => $categories,
        ]);
    }

    #[Route('/category/{id}', name: 'app_category_show', requirements: ['id' => '\d+'])]
    public function show(Category $category, ArticleRepository $articleRepository, Request $request): Response
    {

        $children = $category->getChildren();
        $selectedCategoryId = $request->query->get('category');

        if ($selectedCategoryId) {
            $selectedCategory = null;
            foreach ($children as $child) {
                if ($child->getId() == $selectedCategoryId) {
                    $selectedCategory = $child;
                    break;
                }
            }

            if ($selectedCategory) {
                $articles = $articleRepository->findBy(
                    ['category' => $selectedCategory, 'published' => true],
                    ['createdAt' => 'DESC']
                );
            } else {
                $articles = $articleRepository->findBy(
                    ['category' => $category, 'published' => true],
                    ['createdAt' => 'DESC']
                );
            }
        } else {
            // Inclure la catégorie + toutes ses sous-catégories
            $ids = [$category->getId()];
            foreach ($children as $child) {
                $ids[] = $child->getId();
            }

            $articles = $articleRepository->createQueryBuilder('a')
                ->where('a.category IN (:ids)')
                ->andWhere('a.published = :published')
                ->setParameter('ids', $ids)
                ->setParameter('published', true)
                ->orderBy('a.createdAt', 'DESC')
                ->getQuery()
                ->getResult();
        }

        return $this->render('category/show.html.twig', [
            'category' => $category,
            'children' => $children,
            'articles' => $articles,
            'selectedCategory' => $selectedCategoryId,
        ]);
    }
}
