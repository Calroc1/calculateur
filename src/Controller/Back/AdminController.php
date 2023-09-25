<?php
namespace App\Controller\Back;
 
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

use App\Entity\Admin;

use App\Form\AdminType;
use App\Form\PasswordType;
 
/**
 * Ce controlleur contient toutes les fonctionnalités liées aux administrateurs
 * 
 * @Route("/administrateurs", name="admin_")
 * @IsGranted("ROLE_ADMIN_SUPER")
 */
class AdminController extends AbstractController
{
    /**
     * Page de listing des administrateurs
     * 
     * @return Response
     * 
     * @Route("/", name="home")
     */
    public function home(): Response
    {       
        $admins = $this->getDoctrine()->getRepository(Admin::class)->findAll();
        return $this->render('back/admin/home.html.twig', [
            'admins' => $admins
        ]);
    }

    /**
     * Page de création d'un administrateur
     * 
     * @param Request $request
     * @param UserPasswordEncoderInterface $passwordEncoder
     * 
     * @return Response
     * 
     * @Route("/ajouter", name="add")
     */
    public function add(Request $request, UserPasswordEncoderInterface $passwordEncoder): Response
    {       
        $admin = new Admin();
        $form = $this->createForm(AdminType::class, $admin, [
            'with_password' => 1
        ]);        
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $admin->setPassword($passwordEncoder->encodePassword($admin, $form->get('password')->getData()));                    

            $em = $this->getDoctrine()->getManager(); 
            $em->persist($admin);
            $em->flush();

            $this->addFlash('success', "Le compte admin a bien été créé.");
            return $this->redirectToRoute('back_admin_home');
        }
        return $this->render('back/admin/add.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * Page d'édition d'un administrateur
     * 
     * @param Admin $admin
     * @param Request $request
     * @param UserPasswordEncoderInterface $passwordEncoder
     * 
     * @return Response
     * 
     * @Route("/{admin}", name="update")
     */
    public function update(Admin $admin, Request $request, UserPasswordEncoderInterface $passwordEncoder): Response
    {       
        $formUpdate = $this->createForm(AdminType::class, $admin);
        $formUpdate->handleRequest($request);
        if ($formUpdate->isSubmitted() && $formUpdate->isValid()) {
            $em = $this->getDoctrine()->getManager(); 
            $em->persist($admin);
            $em->flush();

            $this->addFlash('success', "Le compte admin a bien été mis à jour.");
            return $this->redirectToRoute('back_admin_home');
        }
        $formPassword = $this->createForm(PasswordType::class);
        $formPassword->handleRequest($request);
        if ($formPassword->isSubmitted() && $formPassword->isValid()) {
            $admin->setPassword($passwordEncoder->encodePassword($admin, $formPassword->getData()));                    

            $em = $this->getDoctrine()->getManager(); 
            $em->persist($admin);
            $em->flush();
            
            $this->addFlash('success', "Le mot de passe a bien été mis à jour.");
            return $this->redirectToRoute('back_admin_home');
        }
        return $this->render('back/admin/update.html.twig', [
            'formUpdate' => $formUpdate->createView(), 
            'formPassword' => $formPassword->createView()
        ]);
    }

    /**
     * Fonctionnalité de suppression d'un administrateur
     * 
     * @param Admin $admin
     * 
     * @return Response
     * 
     * @Route("/{admin}/supprimer", name="delete")
     */
    public function delete(Admin $admin): Response
    {  
        $em = $this->getDoctrine()->getManager(); 
        $em->remove($admin);
        $em->flush();
        $this->addFlash('success', "Le compte admin a bien été supprimé.");
        return $this->redirectToRoute('back_admin_home');
    }
}