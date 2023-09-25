<?php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;

use App\Entity\Campaign\Campaign;
use App\Entity\Campaign\Variant;
use App\Entity\Support\FormElement;
use App\Entity\Support\Formula;
use App\Entity\Support\Support;
use NXP\MathExecutor;
use Symfony\Component\Security\Core\Security;

/**
 * Ce service contient les fonctionnalités liées aux calculs des statistiques d'une campagne
 */
class StatService
{ 
    private $em; 
    private $shortcodeService = null;
    private $mathExecutor = null;
    private $user;
    private $verbose = false;

    /**
     * @param EntityManagerInterface $em
     * @param ShortcodeService $shortcodeService
     * @param Security $security
     */
    public function __construct(EntityManagerInterface $em, ShortcodeService $shortcodeService, Security $security)
    {
        $this->em = $em;
        $this->shortcodeService = $shortcodeService;
        $this->mathExecutor = new MathExecutor();
        $this->user = $security->getUser();
    }

    /**
     * Permet de définir le variant concernée pour le service de shortcodes
     * 
     * @param Variant $variant
     * 
     * @return void
     */
    public function setShortcodeServiceVariant(Variant $variant): void
    {
        $this->shortcodeService->setVariant($variant);  
    }

    /**
     * Permet d'activer le mode de détection des erreurs
     * 
     * @param bool $verbose
     * 
     * @return void
     */
    public function setVerbose(bool $verbose): void
    {
        $this->verbose = $verbose;
    }

    /**
     * Permet d'obtenir le tableau des statistiques "vierge" avec tous les supports de diffusion
     * 
     * @return array
     */
    public function getBlankStatistics(): array
    {
        $campaign = new Campaign();
        $variant = new Variant($campaign);
        return $this->getVariantStatistics($variant, true, true);
    }

    /**
     * Permet d'obtenir le tableau des statistiques cumulées pour une liste de campagnes
     * 
     * @param array $campaigns
     * 
     * @return array
     */
    public function getCumulativeStatistics($campaigns = []){
        $return = $this->getBlankStatistics();
        foreach($campaigns as $c){
            $v = ($c->getChosenVariant()) ? $c->getChosenVariant() : $c->getVariantByIndex(0);
            if(!$v)
                continue;
            $stats = $this->getVariantStatistics($v);
            foreach ($stats as $supportname => $supportstats) {
                if(isset($return[$supportname])){
                    foreach ($supportstats as $sectionname => $val) {
                        if(isset($return[$supportname][$sectionname]))
                            $return[$supportname][$sectionname] += $val;
                    }
                }                
            }          
        }
        return $return;
    }

    /**
     * Permet d'obtenir le tableau des statistiques d'une variante
     * 
     * @param Variant $variant
     * @param bool $detailed Pour obtenir les statistiques détaillées avec les sous-étapes de chaque support
     * @param bool $allSupports Permet d'alimenter le tableau avec tous les supports et non pas uniquement les supports actifs pour la variante
     * 
     * @return array
     */
    public function getVariantStatistics(Variant $variant, bool $detailed = true, bool $allSupports = false): array
    {
        $return = [];      
        $this->shortcodeService->setVariant($variant);  
        foreach($this->em->getRepository(Support::class)->findEnabledSupports() as $support){
            if($allSupports || $variant->hasSupport($support))
                $return[$support->getName()] = $this->getSupportStatistics($variant, $support, $detailed);
        }
        return $return;
    }

    /**
     * Permet d'obtenir les statistiques d'un support de diffusion pour une variante
     * 
     * @param Variant $variant
     * @param Support $support
     * @param bool $detailed
     * 
     * @return mixed Array ou float
     */
    public function getSupportStatistics(Variant $variant, Support $support, bool $detailed = false)
    {
        $return = [];   
        $this->shortcodeService->setVariant($variant);
        foreach($support->getFormulas() as $formula){
            $total = $this->calculateFormula($formula, $variant, $support->getFormElementByPath($formula->getPath()));
            if($total !== null)
                $return[$formula->getName()] = round($total);
        }
        foreach($return as $k => $v)
            $return[$k] = round($v);
        return $detailed ? $return : array_sum($return);
    } 

    /**
     * Permet de calculer une formule pour une variante
     * 
     * @param Formula $formula Formule à calculer
     * @param Variant $variant
     * @param FormElement $formElement
     * 
     * @return float
     */
    public function calculateFormula(Formula $formula, Variant $variant, ?FormElement $formElement, $data = null){ 
        if(!$formElement)
            return 0;
        if(($phase = $formElement->getPhase()) && (!$variant->hasPhase($phase) || ($this->user && !$this->user->hasPhase($phase))))
            return 0;

        $total = 0;
        if(!$data){
            $data = $variant->getFieldData($formElement);
        }        
        if($formElement->getType() != 'collection'){
            $data = [$data];
        }
        foreach($data as $d){
            $processSelectData = function(&$data, $formElement) use(&$processSelectData){
                if($formElement->getType() == 'section' || $formElement->getType() == 'collection'){
                    foreach($formElement->getChildren() as $child){
                        if(isset($data[$child->getName()])){
                            $processSelectData($data[$child->getName()], $child);
                        }                        
                    }
                }
                else if($formElement->getType() == 'select' || $formElement->getType() == 'select_with_detail') {
                    foreach($formElement->getConfig()['choices'] as $choice){
                        if(isset($choice['label']) && isset($choice['value']) && $choice['label'] == $data)
                        {
                            $data = $choice['value']; 
                            break;
                        }
                    }
                }
            };
            $processSelectData($d, $formElement);
            $this->shortcodeService->setData($d);
            foreach($formula->getVars() as $var){
                $this->shortcodeService->addVars($var->getName(), $this->applyFormula($var));
            }
            if($formula->getFormula()){
                $total += $this->applyFormula($formula);
            }
            foreach($formula->getChildren() as $formulaChild){
                $formElementChild = $formulaChild->getPath() ? $formElement->getChildByPath($formulaChild->getPath()) : $formElement;
                $total += $this->calculateFormula($formulaChild, $variant, $formElementChild);
            }
        }
        return $total;
    }

    /**
     * Permet d'exécuter une formule
     * 
     * @param Formula $formula
     * 
     * @return float
     */
    private function applyFormula(Formula $formula): float
    {
        try {           
            $mathFormula = $this->shortcodeService->convertAll($formula->getFormula());
            $result = $this->mathExecutor->execute($mathFormula);    
            return $result;
        }
        catch (\Exception $e){
            if($this->verbose){
                throw new \Exception("Erreur dans la formule : ".$formula->getFormula());
            }
            return 0;
        }        
    }
}