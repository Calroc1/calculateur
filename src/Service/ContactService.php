<?php

namespace App\Service;

use App\Entity\Campaign\Variant;
use Doctrine\ORM\EntityManagerInterface;

use App\Entity\Campaign\Campaign;
use App\Entity\Support\Support;

/**
 * Ce service contient les fonctionnalités liées aux contacts des campagnes
 */
class ContactService
{
    private $em;

    /**
     * @param EntityManagerInterface $em
     */
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * Permet d'obtenir les budgets d'une variante
     * 
     * @param Variant $variant
     * @param bool $allSupports Permet d'alimenter le tableau avec tous les supports et non pas uniquement les supports actifs pour la variante
     * 
     * @return array|null
     */
    public function getVariantContacts(Variant $variant, bool $allSupports = false): ?array
    {
        if ($variant->getCampaign()->getHasNotionMediaEfficiency() === true) {
            $return = [];
            foreach ($this->em->getRepository(Support::class)->findEnabledSupports() as $support) {
                if ($allSupports || $variant->hasSupport($support)) {
                    $return[$support->getName()] = $this->getSupportContacts($variant, $support);
                }
            }
        } else {
            $return = null;
        }
        return $return;
    }

    /**
     * Permet d'obtenir les contacts pour le support de diffusion d'une variante
     * 
     * @param Variant $variant
     * @param Support $support
     * 
     * @return mixed
     */
    public function getSupportContacts(Variant $variant, Support $support)
    {
        $data = $variant->getSupportMetadata($support);
        if(isset($data['efficiency_media'])){
            return $data['efficiency_media'];
        }
        return [
            'contacts' => 0, 'contacts_unique' => 0
        ];
    }

    /**
     * Permet d'obtenir le tableau des contacts cumulés pour une liste de campagnes
     * 
     * @param array $campaigns
     * 
     * @return array|null
     */
    public function getCumulativeContacts(array $campaigns = []): ?array
    {
        $return = $this->getBlankContacts();
        foreach ($campaigns as $c) {
            $v = ($c->getChosenVariant()) ? $c->getChosenVariant() : $c->getVariantByIndex(0);
            $stats = $this->getVariantContacts($v);
            if ($stats) {
                foreach ($stats as $supportname => $supportstats) {
                    if (!isset($return[$supportname])) {
                        $return[$supportname] = [];
                    }
                    foreach ($supportstats as $sectionname => $val) {
                        if (isset($return[$supportname][$sectionname])) {
                            $return[$supportname][$sectionname] += $val;
                        } else {
                            $return[$supportname][$sectionname] = $val;
                        }
                    }
                }
            }
        }
        return $return;
    }

    /**
     * Permet d'obtenir le tableau des contacts "vierge" avec tous les supports de diffusion
     * 
     * @return array|null
     */
    public function getBlankContacts(): ?array
    {
        $campaign = new Campaign();
        $variant = new Variant($campaign);
        return $this->getVariantContacts($variant, true);
    }
}