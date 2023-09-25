<?php
namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Doctrine\ORM\EntityManagerInterface;

use App\Entity\Admin;

/**
 * Cette commande permet de créer un administrateur
 */
class CreateAdminCommand extends Command
{
    protected static $defaultName = 'app:create:admin';
    
    private $em;
    private $passwordEncoder;
    
    /**
     * @param EntityManagerInterface $em 
     * @param UserPasswordEncoderInterface $passwordEncoder 
     */
    public function __construct(EntityManagerInterface $em, UserPasswordEncoderInterface $passwordEncoder)
    {
        $this->em = $em;
        $this->passwordEncoder = $passwordEncoder;

        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'E-mail')
            ->addArgument('password', InputArgument::REQUIRED, 'Password')
            ->addArgument('super', InputArgument::OPTIONAL, 'Super')
        ;
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln(['Creating user..']);  
        
        $email = $input->getArgument('email');
        $password = $input->getArgument('password');      
        $super = filter_var($input->getArgument('super'), FILTER_VALIDATE_BOOLEAN);          
       
        $admin = new Admin();
        $admin->setEmail($email);
        $admin->setPassword($this->passwordEncoder->encodePassword($admin, $password));
        $admin->setSuper($super);

        $this->em->persist($admin);
        $this->em->flush();

        $output->writeln(['...done...']);        
        return 0;
    }
}

