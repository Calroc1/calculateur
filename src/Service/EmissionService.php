<?php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;

use App\Entity\Emission\Rate;

/**
 * Ce service contient les fonctionnalités liées aux facteurs d'emission
 */
class EmissionService
{ 
    private $em;
    private $_rates = null;
    private $vars = [];

    /**
     * @param EntityManagerInterface $em
     */
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * Permet de charger tous les facteurs d'emission dans la prop  $_rates
     * 
     * @return void
     */
    public function loadRates(): void
    {       
        foreach($this->em->getRepository(Rate::class)->findBy([ 'parent' => null ]) as $r){
            $this->_rates[$r->getName()] = $r;
        }
    }
    
    /**
     * Permet de récupérer la valeur d'un facteur d'émission en fonction de son chemin
     * 
     * @param string ...$names
     * 
     * @return mixed
     */
    public function getRateData(string ...$names)
    {
        $recursive = function($rate) use (&$recursive) {
            if($rate && $rate->getChildren()->count() > 0){
                $return = [];
                foreach($rate->getChildren() as $c){
                    $return[$c->getName()] = $recursive($c);
                }
                return $return;
            }  
            else
                return $rate ? $rate->getCurrentValue() : 0;
        };
        return $recursive($this->getRate(...$names));        
    }

    /**
     * Permet de récupérer un facteur d'émission en fonction de son chemin
     * 
     * @param string ...$names
     * 
     * @return Rate|null
     */
    public function getRate(string ...$names): ?Rate
    {
        if(!$this->_rates)
            $this->loadRates();

        $names = array_filter($names);
        $getRate = function($array, $names) use (&$getRate){
            $name = array_shift($names);
            if(isset($array[$name])){
                if(empty($names))
                    return $array[$name];
                return $getRate($array[$name]->getChildren(), $names);
            }
            else
                return null;
        };
        return $getRate($this->_rates, $names);
    }

    /**
     * Facteur d'émission calculé : consommation moyenne d'un appareil électrique
     * 
     * @param string $aname
     * 
     * @return float
     */
    public function getAppareilConsommationMoy(string $aname): float
    {
        $appareil = $this->getRateData('consommation_electrique', 'appareils', $aname);
        if($appareil)
            return $appareil["kwh/an"]/($appareil['duree']*365.25*3600);
        return 0;
    }

    /**
     * Facteur d'émission calculé : consommation moyenne du pays
     * 
     * @param string $countryCode
     * 
     * @return float
     */
    public function getLocalizationConsommation(string $countryCode): float{
        $return = $this->getRateData('localization', $countryCode);
        return $return ? $return : $this->getWorldConsommationMoy();
    }

    /**
     * Facteur d'émission calculé : consommation mondiale moyenne
     * 
     * @return float
     */
    public function getWorldConsommationMoy(): float
    {
        if(!isset($this->vars['world_consommation_moyen'])){
            $countries = $this->getRateData('localization');
            $this->vars['world_consommation_moyen'] = array_sum($countries) / count($countries);
        }
        return $this->vars['world_consommation_moyen'];
    }
}