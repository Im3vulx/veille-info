<?php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\Favorite;
use App\Repository\CategoryRepository;
use App\Repository\FavoriteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class BookmarksController extends AbstractController
{
    #[Route('/bookmarks', name: 'app_bookmarks')]
    public function index(FavoriteRepository $favoriteRepository, CategoryRepository $categoryRepository, Request $request): Response
    {
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException();
        }

        $favorites = $favoriteRepository->findBy(['user' => $user]);

        $searchTerm = $request->query->get('search', '');
        $categoryFilter = $request->query->get('category', 'all');
        $sortBy = $request->query->get('sort', 'recent');

        $categories = $categoryRepository->findAll();

        return $this->render('bookmarks/index.html.twig', [
            'favorites' => $favorites,
            'searchTerm' => $searchTerm,
            'categoryFilter' => $categoryFilter,
            'sortBy' => $sortBy,
            'categories' => $categories,
        ]);
    }

    #[Route('/bookmarks/remove/{id}', name: 'app_bookmark_remove', methods: ['POST'])]
    public function remove(Article $article, EntityManagerInterface $entityManager): RedirectResponse
    {
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException();
        }

        $favorite = $entityManager->getRepository(Favorite::class)->findOneBy([
            'user' => $user,
            'article' => $article
        ]);

        if ($favorite) {
            $entityManager->remove($favorite);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_bookmarks');
    }

    #[Route('/bookmarks/remove-all', name: 'app_bookmark_remove_all', methods: ['POST'])]
    public function removeAll(EntityManagerInterface $em, FavoriteRepository $favoriteRepository): RedirectResponse
    {
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException();
        }

        $favorites = $favoriteRepository->findBy(['user' => $user]);
        foreach ($favorites as $favorite) {
            $em->remove($favorite);
        }
        $em->flush();

        return $this->redirectToRoute('app_bookmarks');
    }
}
