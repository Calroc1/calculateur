<?php

namespace App\Utils;

/**
 * Cette classe permet de définir des libellés 
 */
class LabelHelpers
{
    /**
     * Statuts des utilisateurs
     * 
     * @return array
     */
    public static function getUserStatuses() :array{
        return [
            'ASSISTANT' => 'Adjoint',
            'SUPERVISOR' => 'Administrateur',
            'GUEST' => 'Invité'
        ];
    }

    /**
     * Statuts des campagnes
     * 
     * @return array
     */
    public static function getCampaignStatuses() :array{
        return [
            'STARTED' => 'Estimation en cours',
            'COMPLETED' => 'Estimation terminée',
            'FINISHED' => 'Post-campagne',
            'ARCHIVED' => 'Archivée',
        ];
    }
}