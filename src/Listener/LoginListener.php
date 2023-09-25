<?php
namespace App\Listener;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

/**
 * Listener pour connexion d'un utilisateur
 */
class LoginListener
{
    private $em;

    public function __construct(EntityManagerInterface $em){
        $this->em = $em;
    }

    public function onSecurityInteractiveLogin(InteractiveLoginEvent $event)
    {
        $user = $event->getAuthenticationToken()->getUser();
        
        // autosave de la dernière date de connexion
        if(method_exists($user, 'setDateLastLogin')){
            $user->setDateLastLogin(new \DateTime());
        }

        $this->em->persist($user);
        $this->em->flush();
    }
}