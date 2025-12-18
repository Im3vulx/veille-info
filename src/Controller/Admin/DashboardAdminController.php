<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Entity\Article;
use App\Entity\Category;
use App\Form\AdminSettingsType;
use App\Repository\UserRepository;
use App\Repository\ArticleRepository;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
final class DashboardAdminController extends AbstractController
{
    /**
     * PAGE 1 : VUE D'ENSEMBLE
     * Ne charge que les statistiques et les résumés.
     */
    #[Route('/dashboard', name: 'app_dashboard_admin')]
    public function index(CategoryRepository $categoryRepository, ArticleRepository $articleRepository): Response
    {
        $countArticles = $articleRepository->countPublished();
        $totalArticles = $articleRepository->count([]);
        $categoriesCount = $categoryRepository->count([]);

        $latestArticles = $articleRepository->findBy(
            [],
            ['createdAt' => 'DESC'],
            4
        );

        $topCategories = $articleRepository->countArticlesByCategory(5);
        $maxCount = 0;
        foreach ($topCategories as $cat) {
            if ($cat['article_count'] > $maxCount) {
                $maxCount = $cat['article_count'];
            }
        }

        return $this->render('admin/dashboard/overview.html.twig', [
            'countarticles' => $countArticles,
            'totalArticles' => $totalArticles,
            'categoriesCount' => $categoriesCount,
            'latestArticles' => $latestArticles,
            'topCategories' => $topCategories,
            'maxCount' => $maxCount,
        ]);
    }

    /**
     * PAGE 2 : GESTION DES ARTICLES
     */
    #[Route('/articles', name: 'app_admin_articles')]
    public function articles(ArticleRepository $articleRepository, CategoryRepository $categoryRepository): Response
    {
        $articles = $articleRepository->createQueryBuilder('a')
            ->orderBy('a.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        return $this->render('admin/dashboard/articles.html.twig', [
            'articles' => $articles,
            'categories' => $categoryRepository->findAll(),
        ]);
    }

    /**
     * PAGE 3 : GESTION DES CATÉGORIES
     */
    #[Route('/categories', name: 'app_admin_categories')]
    public function categories(CategoryRepository $categoryRepository): Response
    {
        return $this->render('admin/dashboard/categories.html.twig', [
            'categories' => $categoryRepository->findAll(),
        ]);
    }

    /**
     * PAGE 4 : GESTION DES UTILISATEURS
     */
    #[Route('/users', name: 'app_admin_users')]
    public function users(UserRepository $userRepository): Response
    {
        return $this->render('admin/dashboard/users.html.twig', [
            'users' => $userRepository->findAll(),
        ]);
    }

    #[Route('/settings', name: 'app_admin_settings')]
    #[IsGranted('ROLE_ADMIN')]
    public function settings(Request $request): Response
    {
        // Données par défaut (simule votre useState initial)
        // Dans une vraie app, récupérez ceci depuis la base de données
        $defaultData = [
            'siteName' => 'Neurogameron',
            'siteUrl' => 'https://neurogameron.fr',
            'siteDescription' => 'Votre plateforme de veille informatique',
            'adminEmail' => 'admin@neurogameron.fr',
            'timezone' => 'Europe/Paris',
            'smtpHost' => 'smtp.gmail.com',
            'smtpPort' => 587,
            'fromEmail' => 'noreply@neurogameron.fr',
            'fromName' => 'Neurogameron',
            'enableRegistration' => true,
            'articlesPerPage' => 10,
            'primaryColor' => '#4f46e5',
            'secondaryColor' => '#7e22ce',
            'accentColor' => '#0d9488',
            'enableDarkMode' => true,
            'defaultTheme' => 'light',
            'emailNotifications' => true,
            'newUserNotifications' => true,
            'newCommentNotifications' => true,
            'systemAlerts' => true
        ];

        $form = $this->createForm(AdminSettingsType::class, $defaultData);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            // TODO: Sauvegarder $data en base de données ou fichier YAML

            $this->addFlash('success', 'Paramètres sauvegardés avec succès !');

            // On redirige pour éviter la resoumission du formulaire
            return $this->redirectToRoute('app_admin_settings');
        }

        return $this->render('admin/dashboard/settings.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    // -------------------------------------------------------------------------
    // ACTIONS CRUD (Create, Read, Update, Delete)
    // -------------------------------------------------------------------------

    #[Route('/articles/{id}/delete', name: 'admin_article_delete', methods: ['POST', 'GET'])]
    public function articleDelete(Article $article, EntityManagerInterface $em): Response
    {
        $em->remove($article);
        $em->flush();

        $this->addFlash('success', 'Article supprimé.');
        return $this->redirectToRoute('admin_articles');
    }

    #[Route('/articles/{id}/edit', name: 'admin_article_edit')]
    public function articleEdit(Article $article, Request $request, EntityManagerInterface $em): Response
    {
        // Logique de formulaire ici...
        return $this->render('admin/article/edit.html.twig', [
            'article' => $article
        ]);
    }

    #[Route('/articles/{id}', name: 'admin_article_show')]
    public function articleShow(Article $article): Response
    {
        return $this->render('admin/article/show.html.twig', [
            'article' => $article
        ]);
    }

    // --- USERS ACTIONS ---

    #[Route('/users/{id}/delete', name: 'admin_user_delete')]
    public function userDelete(User $user, EntityManagerInterface $em): Response
    {
        $em->remove($user);
        $em->flush();
        return $this->redirectToRoute('admin_users');
    }

    #[Route('/users/{id}/edit', name: 'admin_user_edit')]
    public function userEdit(User $user): Response
    {
        // Logique form...
        return $this->render('admin/user/edit.html.twig', ['user' => $user]);
    }

    #[Route('/users/{id}', name: 'admin_user_show')]
    public function userShow(User $user): Response
    {
        return $this->render('admin/user/show.html.twig', ['user' => $user]);
    }

    // --- CATEGORY ACTIONS ---

    #[Route('/categories/{id}/delete', name: 'admin_categories_delete')]
    public function categoryDelete(Category $category, EntityManagerInterface $em): Response
    {
        $em->remove($category);
        $em->flush();
        return $this->redirectToRoute('admin_categories');
    }

    #[Route('/categories/{id}/edit', name: 'admin_category_edit')]
    public function categoryEdit(Category $category): Response
    {
        // Logique form...
        return $this->render('admin/category/edit.html.twig', ['category' => $category]);
    }
}
