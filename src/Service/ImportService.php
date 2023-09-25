<?php

namespace App\Service;

use App\Entity\Campaign\Campaign;
use App\Entity\Support\FormElement;
use App\Entity\Support\Formula;
use App\Entity\Support\Support;
use App\Entity\Campaign\Variant;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Ce service contient toutes les fonctionnalités d'import de la plateform
 */
class ImportService
{ 
    private $em; 
    private $statService;

    /**
     * @param EntityManagerInterface $em
     * @param StatService $statService
     */
    public function __construct(EntityManagerInterface $em, StatService $statService)
    {
        $this->em = $em;
        $this->statService = $statService;
        $this->statService->setVerbose(true);
    }

    /**
     * Permet d'importer en bdd un fichier YAML de formulaire pour un support
     * 
     * @param Support $support
     * @param array $yaml
     * 
     * @return void
     */
    public function form(Support $support, array $yaml): void
    {
        $dbElements = $support->getFormElements()->toArray();
        foreach($yaml as $pos => $yamlChild){
            $fname = $yamlChild['name'];
            if(isset($dbElements[$fname])){
                $dbElement = $dbElements[$fname];                    
            }
            else{
                $dbElement = new FormElement();                
                $support->addFormElement($dbElement);
            }
            $dbElement->setPosition($pos);
            $this->handleFormElement($dbElement, $yamlChild);
        }
        foreach($dbElements as $fname => $dbChild){
            $found = false;
            foreach($yaml as $yamlChild){
                if($fname == $yamlChild['name']){                   
                    $found = true; break;
                }                    
            }
            if(!$found){
                $support->removeFormElement($dbChild);
                $this->em->remove($dbChild);
            }
        }
        $support->setDateUpdateForm(new \DateTime());
        $this->em->persist($support);  
        $this->em->flush();        
    }

    /**
     * Permet d'importer en bdd un fichier YAML d'algorithme pour un support
     * 
     * @param Support $support
     * @param array $yaml
     * 
     * @return void
     */
    public function algorithm(Support $support, $yaml){
        foreach($support->getFormulas() as $formula){
            $this->em->remove($formula);
        }
        $this->em->flush();
        foreach($yaml as $algoSection){                
            $support->addFormula($this->handleFormula($algoSection));
        }
        $support->setDateUpdateAlgorithm(new \DateTime());
        $campaign = new Campaign();
        $variant = new Variant();
        $campaign->addVariant($variant);
        $this->statService->getSupportStatistics($variant, $support);
        $this->em->persist($support);
        $this->em->flush();
    }

    /**
     * Fonction récursive pour transformer un élement de formulaire de array vers objet FormElement
     * 
     * @param FormElement $dbElement
     * @param array $yamlElement
     * 
     * @return void
     */
    protected function handleFormElement(FormElement $dbElement, array $yamlElement): void
    {
        $resolver = new OptionsResolver();        
        $resolver->setDefined([
            'type', 'name', 'label', 'phase', 'unit', 'help', 'addendum', 'linebreak', 'default', 'renamable', 'choices', 'disabled', 'lvl', 'size', 'mapped', 'display', 'percentage', 'children', 'rows', 'bold', 'italic', 'underline'
        ]);
        $resolver->setRequired([
            'type', 'name'
        ]);
        $yamlElement = $resolver->resolve($yamlElement);
        $type = $yamlElement['type'];
        $dbElement->setType($yamlElement['type']);
        $dbElement->setName($yamlElement['name']);        
        $dbElement->setLabel($yamlElement['label'] ?? null);
        $dbElement->setPhase($yamlElement['phase'] ?? "");

        $config = [];
        if(isset($yamlElement['unit']))
            $config['unit'] = $yamlElement['unit'];
        if(isset($yamlElement['help']))
            $config['help'] = $yamlElement['help'];
        if(isset($yamlElement['addendum']))
            $config['addendum'] = $yamlElement['addendum'];        
        if(isset($yamlElement['linebreak']))
            $config['linebreak'] = $yamlElement['linebreak'];
        if(isset($yamlElement['bold']))
            $config['bold'] = $yamlElement['bold'];
        if(isset($yamlElement['italic']))
            $config['italic'] = $yamlElement['italic'];
        if(isset($yamlElement['underline']))
            $config['underline'] = $yamlElement['underline'];
        if(isset($yamlElement['default']))
            $config['default'] = $yamlElement['default'];

        if($type == 'textarea'){
            if(isset($yamlElement['rows']))
                $config['rows'] = $yamlElement['rows'];
        }

        // collection
        if($type == 'collection'){
            if(isset($yamlElement['renamable']))
                $config['renamable'] = $yamlElement['renamable'];
        }

        // select
        if($type == 'select' || $type == 'select_with_detail' ){
            $choices = [];
            if(isset($yamlElement['choices']))
                $choices = $yamlElement['choices'];                
            $config['choices'] = $choices;  
        }
        
        // for "old static" support only
        if(isset($yamlElement['disabled']))
            $config['disabled'] = $yamlElement['disabled'];
        if(isset($yamlElement['lvl']))
            $config['lvl'] = $yamlElement['lvl'];
        if(isset($yamlElement['size']))
            $config['size'] = $yamlElement['size'];
        if(isset($yamlElement['mapped']))
            $dbElement->setMapped($yamlElement['mapped']);   
        
        // section
        $dbChildren = $dbElement->getChildren()->toArray();
        if($type == 'section' || $type == 'collection'){  
            if(isset($yamlElement['display']))
                $config['display'] = $yamlElement['display'];
            if(isset($yamlElement['percentage']))
                $config['percentage'] = $yamlElement['percentage'];
            if(isset($yamlElement['children'])){                
                $pos = 0;
                foreach($yamlElement['children'] as $yamlChild){
                    $cname = $yamlChild['name'];
                    if(isset($dbChildren[$cname])){
                        $dbChild = $dbChildren[$cname];                    
                    }
                    else{
                        $dbChild = new FormElement();
                        $dbElement->addChild($dbChild);
                    }          
                    $dbChild->setPosition($pos);                
                    $dbChild->setLvl($dbElement->getLvl()+1);   
                    $this->handleFormElement($dbChild, $yamlChild);
                    $pos++;
                }
            }
        }

        $dbElement->setConfig($config);

        foreach($dbChildren as $cname => $dbChild){
            $found = false;
            if(isset($yamlElement['children'])){
                foreach($yamlElement['children'] as $yamlChild){
                    if($cname == $yamlChild['name']){
                        $found = true; break;
                    }                    
                }
            }           
            if(!$found){
                $dbElement->removeChild($dbChild);
                $this->em->remove($dbChild);          
            }
        }
    }

    /**
     * Fonction récursive pour transformer une formule de array vers objet Formula
     * 
     * @param array $yamlElement
     * 
     * @return Formula
     */
    private function handleFormula(array $yamlFormula): Formula
    {
        $resolver = new OptionsResolver();        
        $resolver->setDefined([
            'name', 'path', 'formula', 'children', 'vars'
        ]);
        $yamlFormula = $resolver->resolve($yamlFormula);
        $formula = new Formula();
        $formula->setName($yamlFormula['name'] ?? null);
        $formula->setPath($yamlFormula['path'] ?? null);
        $formula->setFormula($yamlFormula['formula'] ?? null);
        if(isset($yamlFormula['children'])){
            foreach($yamlFormula['children'] as $yamlChild){
                $formula->addChild($this->handleFormula($yamlChild));
            }
        }
        if(isset($yamlFormula['vars'])){
            foreach($yamlFormula['vars'] as $yamlVar){
                $formula->addVar($this->handleFormula($yamlVar));
            }
        }
        return $formula;
    }
}