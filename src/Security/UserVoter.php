<?php
namespace App\Security;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

use App\Entity\User;
use App\Entity\Organism;

/**
 * Voter pour accès aux utilisateurs
 */
class UserVoter extends Voter
{
    const VIEW = 'user_view';
    const ADD = 'user_add';
    const UPDATE = 'user_update';
    const DELETE = 'user_delete';

    const CAPABILITIES = [
        self::VIEW => [
            'required' => false,
            'class' => User::class
        ],
        self::ADD => [
            'required' => false,
            'class' => Organism::class
        ],
        self::UPDATE => [
            'required' => true,
            'class' => User::class
        ],
        self::DELETE => [
            'required' => true,
            'class' => User::class
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
                    return $user->containsOrganism($subject->getOrganism());    
                else
                    return $user->getStatus() == 'SUPERVISOR';
            case self::ADD:
                if($subject)
                    return $user->containsOrganism($subject);  
                else
                    return true;
            case self::UPDATE:
                return $user->containsOrganism($subject->getOrganism());         
            case self::DELETE:
                return $user->containsOrganism($subject->getOrganism());                
        }
        return false;
    }
}