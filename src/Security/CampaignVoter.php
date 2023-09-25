<?php
namespace App\Security;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

use App\Entity\Campaign\Campaign;
use App\Entity\User;

/**
 * Voter pour accès aux campagnes
 */
class CampaignVoter extends Voter
{
    const VIEW = 'campaign_view';
    const ADD = 'campaign_add';
    const UPDATE = 'campaign_update';
    const STATS = 'campaign_statistics';
    const DELETE = 'campaign_delete';    

    const CAPABILITIES = [
        self::VIEW => [
            'required' => false,
            'class' => Campaign::class
        ],
        self::ADD => [
            'required' => false,
            'class' => null
        ],
        self::UPDATE => [
            'required' => true,
            'class' => Campaign::class
        ],
        self::DELETE => [
            'required' => true,
            'class' => Campaign::class
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
                return $user->containsOrganism($subject->getOrganism());
            case self::ADD:
                return $user->getStatus() == 'SUPERVISOR';
            case self::UPDATE:
                return $user->containsOrganism($subject->getOrganism());
            case self::DELETE:               
                return $user->getStatus() == 'SUPERVISOR';
        }
        return false;
    }
}