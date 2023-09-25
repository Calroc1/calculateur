<?php

namespace App\Controller\Front;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormError;
use Symfony\Component\Validator\Constraints as Assert;

use App\Entity\User;

use App\Form\PasswordType;

use App\Service\Mailer\UserMailerService;

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
        
        return $this->render('front/security/login.html.twig', [
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

    /**
     * Page d'activation de compte utilisateur via invitation
     * 
     * @param mixed $user
     * @param mixed $token
     * @param Request $request
     * @param UserMailerService $usm
     * @param UserPasswordEncoderInterface $passwordEncoder
     * @param TokenStorageInterface $tokenStorage
     * @param SessionInterface $session
     * 
     * @return Response
     * 
     * @Route("/invitation/{user}/{token}", name="invitation")
     */
    public function invitation($user, $token, Request $request, UserMailerService $usm, UserPasswordEncoderInterface $passwordEncoder, TokenStorageInterface $tokenStorage, SessionInterface $session): Response
    {
        $em = $this->getDoctrine()->getManager(); 
        $user = $em->getRepository(User::class)->findOneById($user);        
        $params = [];
        if($user && $user->getTokenInvitation() == $token){
            $formPassword = $this->createForm(PasswordType::class);
            $formPassword->handleRequest($request);
            if ($formPassword->isSubmitted() && $formPassword->isValid()) {
                $user->setPassword($passwordEncoder->encodePassword($user, $formPassword->getData()));                    

                $user->setTokenInvitation(null);
                $user->setEnabled(true);
    
                
                $em->persist($user);
                $em->flush();
    
                $usm->confirm($user);

                $token = new UsernamePasswordToken($user, $user->getPassword(), 'front', $user->getRoles());
                $tokenStorage->setToken($token);
                $session->set('_security_main', serialize($token));

                $this->addFlash('success', "La création de votre compte est maintenant terminée et vous êtes à présent connecté.");
                return $this->redirectToRoute('front_home');
            }   
            $params['formPassword'] = $formPassword->createView();
        }
        return $this->render('front/security/invitation.html.twig', $params);
    }

    /**
     * Page de demande de nouveau mot de passe
     * 
     * @param Request $request
     * @param UserMailerService $usm
     * 
     * @return Response
     * 
     * @Route("/mot-de-passe-oublie", name="password_request")
     */
    public function requestPassword(Request $request, UserMailerService $usm): Response{  
        $formEmail = $this->createForm(Type\EmailType::class, null, [
            'label' => 'Votre adresse email',
            'constraints' => [
                new Assert\NotNull(),
                new Assert\Email(),
            ]
        ]);
        $formEmail->handleRequest($request);
        if ($formEmail->isSubmitted() && $formEmail->isValid()) {          
            $em = $this->getDoctrine()->getManager(); 
            $user = $em->getRepository(User::class)->findOneByEmail($formEmail->getData());
            if($user){
                $user->setTokenPassword(\App\Utils\Utils::generateToken());            
                $em->persist($user);
                $em->flush();

                $usm->passwordRequest($user);
                $this->addFlash('security_success', "Vous allez recevoir un e-mail avec un lien pour réinitialiser votre mot de passe.");
                return $this->redirectToRoute('front_security_login');
            }
            else
                $formEmail->addError(new FormError("Aucun compte lié à cette adresse e-mail n'a été trouvé."));
        }
        return $this->render('front/security/request-password.html.twig', [
            'formEmail' => $formEmail->createView()
        ]);
    }

    /**
     * Page de réinitialisation de mot de passe
     * 
     * @param User $user
     * @param mixed $token
     * @param Request $request
     * @param UserPasswordEncoderInterface $passwordEncoder
     * 
     * @return Response
     * 
     * @Route("/mot-de-passe-reinitialiser/{user}/{token}", name="password_reset")
     */
    public function resetPassword(User $user, $token, Request $request, UserPasswordEncoderInterface $passwordEncoder): Response{       
        $params = [];
        if($user->getTokenPassword() == $token){
            $formPassword = $this->createForm(PasswordType::class);
            $formPassword->handleRequest($request);
            if ($formPassword->isSubmitted() && $formPassword->isValid()) {
                $user->setPassword($passwordEncoder->encodePassword($user, $formPassword->getData()));                    

                $user->setTokenPassword(null);
                $user->setEnabled(true);
    
                $em = $this->getDoctrine()->getManager(); 
                $em->persist($user);
                $em->flush();
                
                $this->addFlash('security_success', "Votre mot de passe a bien été réinitialisé et vous pouvez à présent vous connecter.");
                return $this->redirectToRoute('front_security_login');
            }
            $params['formPassword'] = $formPassword->createView();
        }
        return $this->render('front/security/reset-password.html.twig', $params);
    }    
}