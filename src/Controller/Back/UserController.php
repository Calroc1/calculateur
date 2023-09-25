<?php
namespace App\Controller\Back;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

use App\Entity\User;
use App\Entity\Organism;

use App\Form\UserType;
use App\Form\PasswordType;

use App\Service\Mailer\UserMailerService;
 
/**
 * Ce controlleur contient toutes les fonctionnalités liées aux utilisateurs
 * 
 * @Route("/utilisateurs", name="user_")
 */
class UserController extends AbstractController
{     
    /**
     * Page de création d'un utilisateur
     * 
     * @param Organism $organism
     * @param Request $request
     * @param UserMailerService $usm
     * 
     * @return Response
     * 
     * @Route("/ajouter/{organism}", name="add")
     */
    public function add(Organism $organism, Request $request, UserMailerService $usm): Response
    {               
        $user = new User();
        $user->setOrganism($organism);
        $form = $this->createForm(UserType::class, $user, [            
            'with_invitation' => true
        ]);       
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager(); 
            $em->persist($user);
            $em->flush();

            if($form->get('invitation')->getData())
                $usm->invitation($user, $this->getUser());
            
            $this->addFlash('success', "L'utilisateur a bien été créé.");
            return $this->redirectToRoute('back_organism_detail', [
                'organism' => $organism->getId()
            ]);
        }
        return $this->render('back/user/add.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * Page d'édition d'un utilisateur
     * 
     * @param User $user
     * @param Request $request
     * @param UserPasswordEncoderInterface $passwordEncoder
     * 
     * @return Response
     *  
     * @Route("/{user}", name="detail")
     */
    public function detail(User $user, Request $request, UserPasswordEncoderInterface $passwordEncoder): Response
    {       
        $formUpdate = $this->createForm(UserType::class, $user);
        $formUpdate->handleRequest($request);
        if ($formUpdate->isSubmitted() && $formUpdate->isValid()) {
            $em = $this->getDoctrine()->getManager(); 
            $em->persist($user);
            $em->flush();

            $this->addFlash('success', "L'utilisateur a bien été mis à jour.");
            return $this->redirectToRoute('back_organism_detail', [
                'organism' => $user->getOrganism()->getId()
            ]);
        }
        $formPassword = $this->createForm(PasswordType::class);
        $formPassword->handleRequest($request);
        if ($formPassword->isSubmitted() && $formPassword->isValid()) {
            $user->setPassword($passwordEncoder->encodePassword($user, $formPassword->getData()));                    

            $em = $this->getDoctrine()->getManager(); 
            $em->persist($user);
            $em->flush();
            
            $this->addFlash('success', "Le mot de passe a bien été mis à jour.");
            return $this->redirectToRoute('back_organism_detail', [
                'organism' => $user->getOrganism()->getId()
            ]);
        }
        return $this->render('back/user/detail.html.twig', [
            'formUpdate' => $formUpdate->createView(), 
            'formPassword' => $formPassword->createView()
        ]);
    }

    /**
     * Fonctionnalité de suppression d'un utilisateur
     * 
     * @param User $user
     * 
     * @return Response
     * 
     * @Route("/{user}/supprimer", name="delete")
     */
    public function delete(User $user): Response
    {  
        $organismId = $user->getOrganism()->getId();
        $em = $this->getDoctrine()->getManager(); 
        $em->remove($user);
        $em->flush();
        $this->addFlash('success', "L'utilisateur a bien été supprimé.");
        return $this->redirectToRoute('back_organism_detail', [
            'organism' => $organismId
        ]);
    }

    /**
     * Fonctionnalité d'invitation d'un utilisateur
     * 
     * @param User $user
     * @param UserMailerService $usm
     * 
     * @return Response
     * 
     * @Route("/{user}/invitation", name="invitation")
     */
    public function invitation(User $user, UserMailerService $usm): Response
    {   
        $user->setTokenInvitation(\App\Utils\Utils::generateToken());
        $em = $this->getDoctrine()->getManager(); 
        $em->persist($user);
        $em->flush();
        $usm->invitation($user, $this->getUser());
        $this->addFlash('success', "L'invitation a bien été envoyée.");
        return $this->redirectToRoute('back_organism_detail', [
            'organism' => $user->getOrganism()->getId()
        ]);
    }
}