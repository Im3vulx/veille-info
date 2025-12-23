<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Form\UserType;
use App\Entity\Article;
use App\Entity\Category;
use App\Form\ArticleType;
use App\Service\SiteSettings;
use App\Form\AdminSettingsType;
use App\Repository\UserRepository;
use App\Repository\ArticleRepository;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

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
    public function settings(Request $request, SiteSettings $siteSettings): Response
    {
        $data = [
            "siteName" => $siteSettings->get('siteName', "Neurogameron"),
            "siteUrl" => $siteSettings->get('siteUrl', "http://localhost:8000"),
            "siteDescription" => $siteSettings->get('siteDescription', "Bienvenue sur Neurogameron, votre source ultime pour tout ce qui concerne les jeux vidéo et la technologie."),
            "adminEmail" => $siteSettings->get('adminEmail', 'admin@neurogameron.fr'),
            "timezone" => $siteSettings->get('timezone', 'Europe/Paris'),
            "smtpHost" => $siteSettings->get('smtpHost', null),
            "smtpPort" => $siteSettings->get('smtpPort', null),
            "smtpUsername" => $siteSettings->get('smtpUsername', null),
            "smtpPassword" => $siteSettings->get('smtpPassword', null),
            "fromEmail" => $siteSettings->get('fromEmail', null),
            "fromName" => $siteSettings->get('fromName', null),
            "enableRegistration" => $siteSettings->get('enableRegistration', false),
            "articlesPerPage" => $siteSettings->get('articlesPerPage', 10),
            'primaryColor' => $siteSettings->get('primaryColor', '#1e3a8a'),
            "secondaryColor" => $siteSettings->get('secondaryColor', '#2563eb'),
            "accentColor" => $siteSettings->get('accentColor', '#3b82f6'),
            "enableDarkMode" => $siteSettings->get('enableDarkMode', false),
            "defaultTheme" => $siteSettings->get('defaultTheme', 'dark'),
            "emailNotifications" => $siteSettings->get('emailNotifications', true),
            "newUserNotifications" => $siteSettings->get('newUserNotifications', true),
            "newCommentNotifications" => $siteSettings->get('newCommentNotifications', true),
            "systemAlerts" => $siteSettings->get('systemAlerts', true),
            "requireEmailVerification" => $siteSettings->get('requireEmailVerification', false),
            "enableTwoFactor" => $siteSettings->get('enableTwoFactor', false),
            "sessionTimeout" => $siteSettings->get('sessionTimeout', 24),
            "maxLoginAttempts" => $siteSettings->get('maxLoginAttempts', 5),
            "enableComments" => $siteSettings->get('enableComments', true),
            "moderateComments" => $siteSettings->get('moderateComments', true),
            "enableBookmarks" => $siteSettings->get('enableBookmarks', true),
            "enableNewsletter" => $siteSettings->get('enableNewsletter', true),
        ];

        $form = $this->createForm(AdminSettingsType::class, $data);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $siteSettings->set($form->getData());

            $this->addFlash('success', 'Paramètres mis à jour avec succès !');
            return $this->redirectToRoute('app_admin_settings');
        }

        return $this->render('admin/dashboard/settings/index.html.twig', [
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
        $form = $this->createForm(ArticleType::class, $article);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $article->setUpdatedAt(new \DateTimeImmutable());

            $em->flush();

            $this->addFlash('success', 'L\'article a bien été modifié.');

            return $this->redirectToRoute('admin_article_show', ['id' => $article->getId()]);
        }

        return $this->render('admin/article/edit.html.twig', [
            'article' => $article,
            'form' => $form->createView()
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
    public function userEdit(User $user, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Utilisateur modifié avec succès.');

            return $this->redirectToRoute('admin_user_show', ['id' => $user->getId()]);
        }

        return $this->render('admin/user/edit.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/users/{id}', name: 'admin_user_show')]
    public function userShow(User $user): Response
    {
        return $this->render('admin/user/show.html.twig', [
            'user' => $user
        ]);
    }

    // --- CATEGORY ACTIONS ---

    #[Route('/categories/{id}/delete', name: 'admin_categories_delete')]
    public function categoryDelete(Category $category, EntityManagerInterface $em): Response
    {
        $em->remove($category);
        $em->flush();
        return $this->redirectToRoute('admin_categories');
    }
}
