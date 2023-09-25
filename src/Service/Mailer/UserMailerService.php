<?php

namespace App\Service\Mailer;

use App\Entity\User;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use App\Service\Mailer\MailerService;

/**
 * Ce service contient les fonctionnalités d'envoi d'email aux utilisateurs de la plateforme
 */
class UserMailerService extends MailerService
{
    /**
     * Permet d'envoyer une invitation à un utilisateur
     * 
     * @param User $user Destinataire de l'invitation
     * @param User|Admin $sender Expéditeur de l'invitation
     * 
     * @return void
     */
    public function invitation(User $user, $sender): void
    {
        $to = [ 
            ['email' => $user->getEmail() ] 
        ];
        $this->sendTemplate($to, 1, [
            'recipient' => $user->__toString(),
            'sender' => $sender->__toString(),
            'url' => $this->router->generate('front_security_invitation', [
                'user' => $user->getId(), 'token' => $user->getTokenInvitation()
            ], UrlGeneratorInterface::ABSOLUTE_URL)
        ]);
    }

    /**
     * Permet d'envoyer l'email de validation de création de compte
     * 
     * @param User $user
     * 
     * @return void
     */
    public function confirm(User $user): void
    {
        $to = [ 
            ['email' => $user->getEmail() ] 
        ];
        $this->sendTemplate($to, 3, [
            'recipient' => $user->__toString(),
            'email' => $user->getEmail(),
            'url' => $this->router->generate('front_home', [], UrlGeneratorInterface::ABSOLUTE_URL)
        ]);
    }

    /**
     * Permet d'envoyer l'email de réinitialisation de mot de passe
     * 
     * @param User $user
     * 
     * @return void
     */
    public function passwordRequest(User $user): void
    {
        $to = [ 
            ['email' => $user->getEmail() ] 
        ];
        $this->sendTemplate($to, 2, [
            'recipient' => $user->__toString(),
            'email' => $user->getEmail(),
            'url' => $this->router->generate('front_security_password_reset', [
                'user' => $user->getId(), 'token' => $user->getTokenPassword()
            ], UrlGeneratorInterface::ABSOLUTE_URL)
        ]);
    }
}