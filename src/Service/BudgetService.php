<?php

namespace App\Service;

use App\Entity\Campaign\Variant;
use Doctrine\ORM\EntityManagerInterface;

use App\Entity\Campaign\Campaign;
use App\Entity\Support\Support;

/**
 * Ce service contient les fonctionnalités liées aux budgets des campagnes
 */
class BudgetService
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
    public function getVariantBudgets(Variant $variant, bool $allSupports = false): ?array
    {
        if ($variant->getCampaign()->getNotionBudget() === Campaign::NOTION_BUDGET_YES_CAMPAIGN) {
            $return = ['global' => $variant->getCampaign()->getBudget()];
        } elseif ($variant->getCampaign()->getNotionBudget() === Campaign::NOTION_BUDGET_YES_MEDIA) {
            $return = ["global" => 0];

            foreach ($this->em->getRepository(Support::class)->findEnabledSupports() as $support) {
                if ($allSupports || $variant->hasSupport($support)) {
                    $budget = $this->getSupportBudgets($variant, $support);

                    if ($budget !== null) {
                        $return[$support->getName()] = $this->getSupportBudgets($variant, $support);
                        $return["global"] += $return[$support->getName()];
                    }
                }
            }
        } else {
            $return = null;
        }

        return $return;
    }

    /**
     * Permet d'obtenir le budget pour le support de diffusion d'une variante
     * 
     * @param Variant $variant
     * @param Support $support
     * 
     * @return float|null
     */
    public function getSupportBudgets(Variant $variant, Support $support): ?float
    {
        $data = $variant->getSupportMetadata($support);
        if(isset($data['budget'])){
            return $data['budget']['budget'];
        }
        return null;
    }

    /**
     * Permet d'obtenir le tableau des budgets cumulés pour une liste de campagnes
     * 
     * @param array $campaigns
     * 
     * @return array|null
     */
    public function getCumulativeBudgets(array $campaigns = []): ?array
    {
        $return = $this->getBlankBudgets();
        foreach ($campaigns as $c) {
            $v = ($c->getChosenVariant()) ? $c->getChosenVariant() : $c->getVariantByIndex(0);
            $stats = $this->getVariantBudgets($v);
            if ($stats) {
                foreach ($stats as $supportname => $val) {
                    if (isset($return[$supportname])) {
                        $return[$supportname] += $val;
                    } else {
                        $return[$supportname] = $val;
                    }
                }
            }
        }
        return $return;
    }

    /**
     * Permet d'obtenir le tableau des budgets "vierge" avec tous les supports de diffusion
     * 
     * @return array|null
     */
    public function getBlankBudgets(): ?array
    {
        $campaign = new Campaign();
        $variant = new Variant($campaign);

        return $this->getVariantBudgets($variant, true);
    }
}