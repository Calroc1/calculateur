<?php
namespace App\Security;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

use App\Entity\User;
use App\Entity\Organism;

/**
 * Voter pour accès aux organismes
 */
class OrganismVoter extends Voter
{
    const VIEW = 'organism_view';    
    const ADD = 'organism_add';  
    const UPDATE = 'organism_update';  
    const DELETE = 'organism_delete';

    const CAPABILITIES = [
        self::VIEW => [
            'required' => false,
            'class' => Organism::class
        ],        
        self::ADD => [
            'required' => false,
            'class' => null
        ],    
        self::UPDATE => [
            'required' => true,
            'class' => Organism::class
        ],    
        self::DELETE => [
            'required' => true,
            'class' => Organism::class
        ]
    ];

    /**
     * {@inheritdoc}
     */
    protected function supports($attribute, $subject): bool
    {
        if (!isset(self::CAPABILITIES[$attribute])) {
            return false;
        }       
        $config = self::CAPABILITIES[$attribute];
        if ($config['required'] && !is_a($subject, $config['class'])) {
            return false;
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }      
        switch ($attribute) {
            case self::VIEW:
                if($subject)
                    return $user->containsOrganism($subject);
                else
                    return $user->getStatus() == 'SUPERVISOR';
            case self::ADD:
                return $user->getOrganism()->getLvl() == 0;
            case self::UPDATE:               
                return $subject->getLvl() > 0 && $user->containsOrganism($subject);
            case self::DELETE:
                return $subject->getLvl() > 0 && $user->containsOrganism($subject, true);
        }
        return false;
    }
}