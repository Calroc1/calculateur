<?php

namespace App\Controller\Back;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

/**
 * Ce controlleur contient toutes les fonctionnalités liées à la sécurité
 * 
 * @Route("", name="security_")
 */
class SecurityController extends AbstractController
{   
    /**
     * Page de connexion
     * 
     * @param AuthenticationUtils $authenticationUtils
     * 
     * @return Response
     * 
     * @Route("/connexion", name="login")
     */
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();
        
        return $this->render('back/security/login.html.twig', [
            'last_username' => $lastUsername,
            'error'         => $error
        ]);
    }

    /**
     * Fonctionnalité nécessaire au système de connexion de symfony
     * 
     * @Route("/login-check", name="login_check")
     */
    public function loginCheck(){}
    
    /**
     * Fonctionnalité nécessaire au système de déconnexion de symfony
     * 
     * @Route("/deconnexion", name="logout")
     */
    public function logout(){}
}
