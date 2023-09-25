<?php
namespace App\Security;

use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

use App\Entity\User;

/**
 * Vérification effectuée après la connexion d'un utilisateur
 */
class UserChecker implements UserCheckerInterface
{    
    public function checkPreAuth(UserInterface $user){
        if (!$user instanceof User){
            return;    
        } 
        if (!$user->getEnabled()){
            throw new CustomUserMessageAuthenticationException("Ce compte n'a pas encore été activé");
        }
        if (!$user->getOrganism()){
            throw new CustomUserMessageAuthenticationException("Ce compte n'est associé à aucune entreprise/organisation");
        }
    }
    
    public function checkPostAuth(UserInterface $user){
        if (!$user instanceof User)
            return;
    }
}