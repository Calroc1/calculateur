<?php
namespace App\Command\Emission;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Doctrine\ORM\EntityManagerInterface;

use App\Entity\Emission\Rate;
use App\Entity\Emission\Value;

/**
 * Cette commande permet d'importer les facteurs d'émissions par défaut présents dans le fichier data/emissions.json
 */
class DefaultizeCommand extends Command
{
    protected static $defaultName = 'app:emission:defaultize';
    
    private $em;
    private $file = 'data/emissions.json';
    
    /**
     * @param EntityManagerInterface $em 
     */
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;

        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this
            ->addOption('purge', null, InputOption::VALUE_NONE, 'Purge') // option suppression de tous les facteurs avant l'import
        ;
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {        
        if($input->getOption('purge')){
            $output->writeln(["...purging all rates..."]);
            $rates = $this->em->getRepository(Rate::class)->findBy([ 'parent' => null ]);
            foreach($rates as $r)
                $this->em->remove($r);
            
            $this->em->flush();
        }    

        $jsonRates = json_decode(file_get_contents($this->file), true);
        foreach($jsonRates as $k => $v){            
            $this->jsonToEntity($k, null, $v);
        }
        $this->em->flush();

        $output->writeln(['...done...']);        
        return 0;
    }

    /**
     * Permet de convertir un facteur d'émission du format json vers l'entité Rate
     * 
     * @param string $name 
     * @param Rate|null $parent
     * @param array $jsonElement
     * 
     * @return Rate
     */
    protected function jsonToEntity($name, $parent, $jsonElement){ 
        $dbElement = $this->em->getRepository(Rate::class)->findOneBy([
            'parent' => $parent, 'name' => $name
        ]);
        if(!$dbElement){
            $dbElement = new Rate();
            $dbElement->setName($name);
            $this->em->persist($dbElement);
        } 
        $dbElement->setLabel(isset($jsonElement['label']) ? $jsonElement['label'] : "");
        $dbElement->setComment(isset($jsonElement['comment']) ? $jsonElement['comment'] : "");
        $dbElement->setUnit(isset($jsonElement['unit']) ? $jsonElement['unit'] : "");
        $dbElement->setSource(isset($jsonElement['source']) ? $jsonElement['source'] : "");
        foreach($dbElement->getValues() as $v){
            $dbElement->removeValue($v);
        } 

        if(isset($jsonElement['children'])){                  
            $dbChildren = $dbElement->getChildren();     
            foreach($jsonElement['children'] as $cname => $jsonChild){
                $dbChild = $this->jsonToEntity($cname, $dbElement, $jsonChild);
                $dbElement->addChild($dbChild);
            }
            foreach($dbChildren as $cname => $dbChild){
                if(!isset($jsonElement['children'][$cname])){
                    $dbElement->removeChild($dbChild);
                }
            }
        }
        else{
            foreach($dbElement->getChildren() as $c){
                $dbElement->removeChild($c);
            }           
            $value = new Value();
            $value->setValue(isset($jsonElement['value']) ? $jsonElement['value'] : $jsonElement);
            $dbElement->addValue($value);
        }
        return $dbElement;
    }
}

