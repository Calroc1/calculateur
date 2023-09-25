<?php

namespace App\Controller\Front;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

use App\Entity\User;
use App\Entity\Organism;

use App\Form\UserType;

use App\Service\Mailer\UserMailerService;

/**
 * Ce controlleur contient toutes les fonctionnalités liées aux utilisateurs
 * 
 * @Route("/collaborateurs", name="user_")
 * @IsGranted("user_view")
 */
class UserController extends AbstractController
{
    /**
     * Page de création d'un utilisateur
     * 
     * @param Organism|null $organism
     * @param Request $request
     * @param UserMailerService $usm
     * 
     * @return Response
     * 
     * @Route("/ajouter/{organism}", name="add", defaults={"organism" = null})
     * @IsGranted("user_add", subject="organism")
     */
    public function addUser(Organism $organism = null, Request $request, UserMailerService $usm): Response
    {
        $user = new User();
        if ($organism) {
            $user->setOrganism($organism);
        } else if ($this->getUser()->getOrganism()->getLvl() != 0) {
            $user->setOrganism($this->getUser()->getOrganism());
        }
        $form = $this->createForm(UserType::class, $user, [
            'with_status' => true,
            'with_organism' => true,
            'with_phases' => true
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($user);
            $em->flush();

            //if($form->get('invitation')->getData())
                $usm->invitation($user, $this->getUser());

            $this->addFlash('success', "Le collaborateur a bien été créé et l'invitation a été envoyée.");
            return $this->redirectToRoute('front_organism_home');
        }
        return $this->render('front/user/add.html.twig', [
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
     * @Route("/{user}/editer", name="update")
     * @IsGranted("user_update", subject="user")
     */
    public function update(User $user, Request $request, UserPasswordEncoderInterface $passwordEncoder): Response
    {
        $formUpdate = $this->createForm(UserType::class, $user, [
            'with_status' => true,
            'with_organism' => true,
            'with_phases' => true
        ]);
        $formUpdate->handleRequest($request);
        if ($formUpdate->isSubmitted() && $formUpdate->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($user);
            $em->flush();

            $this->addFlash('success', "Le collaborateur a bien été mis à jour.");
            return $this->redirectToRoute('front_organism_home');
        }
        return $this->render('front/user/update.html.twig', [
            'formUpdate' => $formUpdate->createView()
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
     * @IsGranted("user_delete", subject="user")
     */
    public function delete(User $user): Response
    {
        $em = $this->getDoctrine()->getManager();
        $em->remove($user);
        $em->flush();
        $this->addFlash('success', "Le collaborateur a bien été supprimé.");
        return $this->redirectToRoute('front_organism_home');
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
     * @IsGranted("user_update", subject="user")
     */
    public function invitation(User $user, UserMailerService $usm): Response
    {
        $user->setTokenInvitation(\App\Utils\Utils::generateToken());
        $em = $this->getDoctrine()->getManager();
        $em->persist($user);
        $em->flush();
        $usm->invitation($user, $this->getUser());
        $this->addFlash('success', "L'invitation a bien été envoyée.");
        return $this->redirectToRoute('front_organism_home');
    }

    /**
     * Endpoint ajax : récupération d'un utilisateur
     * 
     * @param User $user
     * @param SerializerInterface $serializer
     * 
     * @return Response
     * 
     * @Route("/{user}/fetch", condition="request.isXmlHttpRequest()", options={"expose"=true}, name="fetch")
     * @IsGranted("user_view", subject="user")
     */
    public function fetch(User $user, SerializerInterface $serializer): Response
    {
        return $this->json($serializer->normalize($user, null, ['groups' => 'user:edit']));
    }
}
