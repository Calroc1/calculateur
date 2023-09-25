<?php

namespace App\Service;

use App\Entity\Campaign\Variant;

/**
 * Ce service contient toutes les fonctionnalités concernant les shortcodes des formules
 */
class ShortcodeService
{

    CONST REGEX = '/__({{SHORTCODE}})\[([a-zA-Z0-9_. +-]+)(,([a-zA-Z0-9_. -]+]*))?]__/';
    const SHORTCODES = [
        'FCT', 'FIELD', 'VAR', 'EMISSION'
    ];
    
    private Variant $variant;
    private $data = [];
    private $vars = [];

    private $emissionService;

    /**
     * @param EmissionService $emissionService
     */
    public function __construct(EmissionService $emissionService)
    {
        $this->emissionService = $emissionService;
    }

    /**
     * Permet de définir la variant de campagne concernée par les shortcodes
     * 
     * @param Variant $variant
     * 
     * @return void
     */
    public function setVariant(Variant $variant): void
    {
        $this->variant = $variant;
    }

    /**
     * Permet de définir les données concernées qui vont servir à remplacer les shortcodes
     * 
     * @param array $data
     * 
     * @return void
     */
    public function setData(array $data): void
    {
        $this->data = $data;
    }

    /**
     * Permet de stocker temporairement une variable intermédiaire pour utilisation ultérieure
     * 
     * @param string $name
     * @param float $value
     * 
     * @return void
     */
    public function addVars(string $name, float $value): void
    {
        $this->vars[$name] = $value;
    }

    /**
     * Permet de convertir tous les shortcodes d'une chaine de caractères
     * 
     * @param string $string
     * 
     * @return string
     */
    public function convertAll(string $string): string
    {   
        $string = (string) $string;  
        foreach($this::SHORTCODES as $shortcode){
            $regex = str_replace('{{SHORTCODE}}', $shortcode, $this::REGEX);
            $string = preg_replace_callback($regex, function ($match) {
                try {
                    $fct = strtolower($match[1]);
                    $arg1 = $match[2];
                    $arg2 = isset($match[4]) ? trim($match[4]) : null;
                    return $this->{$fct}($arg1, $arg2);
                } catch (\Exception $e){
                    return "";
                } 
            }, $string);
        }
        return $string;
    }

    /**
     * Permet de convertir un shortcode FIELD (champ de formulaire)
     * 
     * @param string $pathname Chemin vers l'élément de formulaire
     * 
     * @return mixed
     */
    public function field(string $pathname)
    {
        $data = $this->data;
        foreach(explode('.', $pathname) as $name){
            if(isset($data[$name])){
                $data = $data[$name];
            }
            else
                return 0;
        }
        return $data;
    }

    /**
     * Permet de convertir un shortcode VAR (variable intermédiaire)
     * 
     * @param string $name Nom de la variable intermédiaire
     * 
     * @return mixed
     */
    public function var(string $name)
    {
        return $this->vars[$name];
    }

    /**
     * Permet de convertir un shortcode EMISSION (Facteur d'émission)
     * 
     * @param string $pathname Chemin vers le facteur d'émission
     * 
     * @return mixed
     */
    public function emission(string $pathname)
    {
        return $this->emissionService->getRateData(...explode('.', $pathname));   
    }   

    /**
     * Permet de convertir un shortcode FCT (Fonction)
     * 
     * @param string $name Nom de la fonction
     * @param mixed $detail Autre argument divers
     * 
     * @return mixed
     */
    public function fct(string $name, $detail)
    {
        switch($name){
            case 'campaign_conso';
                return $this->emissionService->getLocalizationConsommation($this->variant->getCampaign()->getCountry());
            case 'world_avg_conso';
                return $this->emissionService->getWorldConsommationMoy();
            case 'device_avg_conso';
                return $this->emissionService->getAppareilConsommationMoy($detail);
        }
        return 0;
    }
}
?>