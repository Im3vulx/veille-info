<?php

namespace App\Controller;

use App\Form\ProfileFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard')]
    public function index(Request $request): Response
    {
        $activeTab = $request->query->get('tab', 'profile');

        return $this->render('dashboard/index.html.twig', [
            'active_tab' => $activeTab,
            'message' => null,
            'user' => $this->getUser(),
        ]);
    }

    #[Route('/profile/update', name: 'app_profile_update', methods: ['POST'])]
    public function updateProfile(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(ProfileFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Profil mis à jour !');
            return $this->redirectToRoute('app_dashboard');
        }

        return $this->render('dashboard/index.html.twig', [
            'active_tab' => 'profile',
            'message' => null,
        ]);
    }

    #[Route('/profile/password', name: 'app_profile_password', methods: ['POST'])]
    public function updatePassword(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException();
        }

        // Récupération des données du formulaire
        $currentPassword = $request->request->get('current_password');
        $newPassword = $request->request->get('new_password');
        $confirmPassword = $request->request->get('confirm_password');

        if ($newPassword !== $confirmPassword) {
            $this->addFlash('error', 'Les mots de passe ne correspondent pas.');
            return $this->redirectToRoute('app_dashboard', ['tab' => 'password']);
        }

        // Vous ajouterez plus tard la vérification du mot de passe actuel + hash
        $user->setPassword($newPassword);
        $entityManager->flush();

        $this->addFlash('success', 'Mot de passe mis à jour !');
        return $this->redirectToRoute('app_dashboard', ['tab' => 'password']);
    }

    #[Route('/profile/preferences', name: 'app_profile_preferences', methods: ['POST'])]
    public function updatePreferences(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException();
        }

        // Exemple simple : récupération des catégories préférées
        $categories = $request->request->all('categories');

        // Vous pourrez plus tard lier ceci à une vraie propriété User
        $user->setPreferences($categories);
        $entityManager->flush();

        $this->addFlash('success', 'Préférences mises à jour !');
        return $this->redirectToRoute('app_dashboard', ['tab' => 'preferences']);
    }
}
