<?php
namespace App\Controller\Back;
 
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
 
/**
 * Ce controlleur contient toutes les fonctionnalités liées à l'interface d'administration de la plateforme
 */
class InterfaceController extends AbstractController
{
    /**
     * Page d'accueil
     * 
     * @return Response
     * 
     * @Route("/", name="home")
     */
    public function home(): Response
    {       
        return $this->render('back/home.html.twig');
    }
}