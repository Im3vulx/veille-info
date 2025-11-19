<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ConditionsUtilisationController extends AbstractController
{
    #[Route('/conditions/utilisation', name: 'app_conditions_utilisation')]
    public function index(): Response
    {
        return $this->render('conditions_utilisation/index.html.twig', [
            'controller_name' => 'ConditionsUtilisationController',
        ]);
    }
}
