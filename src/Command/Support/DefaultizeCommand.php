<?php
namespace App\Command\Support;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Finder\Finder;

use App\Entity\Support\Support;
use App\Entity\Support\Referential;
use App\Entity\Support\Type as SupportType;
use App\Service\ImportService;
use Symfony\Component\Yaml\Yaml;

/**
 * Cette commande permet d'importer les formulaires par défaut qui sont présents dans le dossier data/campaign/
 */
class DefaultizeCommand extends Command
{
    protected static $defaultName = 'app:support:defaultize';
    
    private $em;
    private $importService;
    private $folderSupports = 'data/supports/forms/';    
    private $folderAlgos = 'data/supports/algos/';   
    
    // référentiels par défaut
    private $referentials = [
        'Bilobay'
    ];

    // supports par défaut
    private $types = [
        'Affichage', 'Presse', 'Supports papier', 'Tv, cinéma, film', 'Radio', 'Internet', 'Campagne digitale', 'Réseaux sociaux', 'Emailing', 
    ];
    
    /**
     * @param EntityManagerInterface $em 
     * @param ImportService $importService 
     */
    public function __construct(EntityManagerInterface $em, ImportService $importService)
    {
        $this->em = $em;
        $this->importService = $importService;

        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this
            ->addOption('purge', null, InputOption::VALUE_NONE, 'Purge') // option suppression de tous les supports avant l'import
        ;
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {        
        // referentials
        $output->writeln(["...creating/updating all referentials..."]);
        foreach($this->referentials as $r){
            $referential = $this->em->getRepository(Referential::class)->findOneByName($r);
            if(!$referential){
                $referential = new Referential();
                $referential->setName($r);
                $this->em->persist($referential);
            }
        }
        // types
        $output->writeln(["...creating/updating all types..."]);
        foreach($this->types as $s){
            $type = $this->em->getRepository(SupportType::class)->findOneByName($s);
            if(!$type){
                $type = new SupportType();
                $type->setName($s);
                $this->em->persist($type);
            }
        }

        $this->em->flush();
        if($input->getOption('purge')){
            $output->writeln(["...purging all supports..."]);
            foreach($this->em->getRepository(Support::class)->findAll() as $s)
                $this->em->remove($s);
            
            $this->em->flush();
        }
        $finder = new Finder();
        $finder->files()->in($this->folderSupports)->name('*.yml');
        $finder->sortByName();
        $pos = 0;
        $output->writeln(["...creating/updating all supports..."]);
        foreach ($finder as $file) {
            preg_match("/([0-9]_)(.*)(.yml)/", $file->getFilename(), $matches);
            
            $output->writeln(["Reading ".$file->getFilename().".."]);
            $yamlElement = Yaml::parseFile($file->getPathName());
            
            $output->writeln(["Update db form element.."]);
            $support = $this->em->getRepository(Support::class)->findOneBy([
                'name' => $yamlElement['name']
            ]);
            if(!$support){
                $support = new Support();
                $support->setName($yamlElement['name']); 
            }        
            $support->setLabel($yamlElement['label']);
            $support->setColor($yamlElement['color']);
            $support->setEnabled(1);
            $support->setPosition($pos);
            $support->setReferential($this->em->getRepository(Referential::class)->findOneByName($yamlElement['referential']));
            $support->setType($this->em->getRepository(SupportType::class)->findOneByName($yamlElement['type']));
            $this->importService->form($support, $yamlElement['children']);
            $this->em->persist($support);
            $this->em->flush();

            if(file_exists($this->folderAlgos.$matches[2].".yml")){
                $output->writeln(["Update db algorithm.."]);
                $yamlFormulas = Yaml::parseFile($this->folderAlgos.$matches[2].".yml");
                $this->importService->algorithm($support, $yamlFormulas);
            }
            $pos++;
        }
        $output->writeln(['...done...']);        
        return 0;
    }
}

